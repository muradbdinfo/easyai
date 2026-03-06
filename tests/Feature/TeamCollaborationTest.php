<?php

// FILE: tests/Feature/TeamCollaborationTest.php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $admin;
    private User   $member;
    private Plan   $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name'                => 'Starter',
            'monthly_token_limit' => 500000,
            'price'               => 29,
            'features'            => [],
            'is_active'           => true,
        ]);

        $this->tenant = Tenant::create([
            'name'        => 'Test Workspace',
            'slug'        => 'test-workspace',
            'plan_id'     => $this->plan->id,
            'token_quota' => 500000,
            'tokens_used' => 0,
            'status'      => 'active',
        ]);

        $this->admin = User::create([
            'name'      => 'Admin User',
            'email'     => 'admin@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->member = User::create([
            'name'      => 'Member User',
            'email'     => 'member@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'member',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
    }

    // ── Test 1: Admin can invite a member ─────────────────────────
    public function test_admin_can_invite_member(): void
    {
        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->post('/team/invite', [
                'email' => 'newuser@test.com',
                'role'  => 'member',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('team_invitations', [
            'tenant_id'  => $this->tenant->id,
            'email'      => 'newuser@test.com',
            'role'       => 'member',
            'status'     => 'pending',
        ]);
    }

    // ── Test 2: Member cannot invite ──────────────────────────────
    public function test_member_cannot_invite(): void
    {
        $response = $this->actingAs($this->member)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->post('/team/invite', [
                'email' => 'newuser@test.com',
                'role'  => 'member',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('team_invitations', [
            'email' => 'newuser@test.com',
        ]);
    }

    // ── Test 3: Cannot invite existing tenant member ──────────────
    public function test_cannot_invite_existing_member(): void
    {
        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->post('/team/invite', [
                'email' => $this->member->email,
                'role'  => 'member',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    // ── Test 4: Accept invitation — new user ──────────────────────
    public function test_accept_invitation_creates_new_user(): void
    {
        $teamService = new TeamService();
        $invitation  = $teamService->createInvitation(
            $this->tenant,
            $this->admin,
            'brand-new@test.com',
            'member'
        );

        $response = $this->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->post("/invitation/{$invitation->token}/accept", [
                'name'                  => 'Brand New',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'email'     => 'brand-new@test.com',
            'tenant_id' => $this->tenant->id,
            'role'      => 'member',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('team_invitations', [
            'id'     => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    // ── Test 5: Accept invitation — existing user (logged in) ──────
    public function test_accept_invitation_links_existing_user(): void
    {
        $existingUser = User::create([
            'name'      => 'Existing',
            'email'     => 'existing@test.com',
            'password'  => bcrypt('p'),
            'role'      => 'member',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $teamService = new TeamService();
        $invitation  = $teamService->createInvitation(
            $this->tenant,
            $this->admin,
            'existing@test.com',
            'admin'
        );

        $response = $this->actingAs($existingUser)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->post("/invitation/{$invitation->token}/accept");

        $response->assertRedirect('/dashboard');

        $this->assertEquals($this->tenant->id, $existingUser->fresh()->tenant_id);
        $this->assertEquals('admin', $existingUser->fresh()->role);
    }

    // ── Test 6: Expired invitation is rejected ────────────────────
    public function test_expired_invitation_is_rejected(): void
    {
        $invitation = TeamInvitation::create([
            'tenant_id'  => $this->tenant->id,
            'invited_by' => $this->admin->id,
            'email'      => 'expired@test.com',
            'role'       => 'member',
            'token'      => TeamInvitation::generateToken(),
            'status'     => 'pending',
            'expires_at' => now()->subDay(), // already expired
        ]);

        $response = $this->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->get("/invitation/{$invitation->token}");

        // Should show error (Inertia renders Accept page with error prop)
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('error'));
    }

    // ── Test 7: Admin can change member role ──────────────────────
    public function test_admin_can_change_member_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->put("/team/members/{$this->member->id}/role", [
                'role' => 'admin',
            ]);

        $response->assertRedirect();
        $this->assertEquals('admin', $this->member->fresh()->role);
    }

    // ── Test 8: Member cannot change another member's role ─────────
    public function test_member_cannot_change_role(): void
    {
        $response = $this->actingAs($this->member)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->put("/team/members/{$this->admin->id}/role", [
                'role' => 'member',
            ]);

        $response->assertStatus(403);
    }

    // ── Test 9: Admin can deactivate a member ─────────────────────
    public function test_admin_can_deactivate_member(): void
    {
        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->put("/team/members/{$this->member->id}/status");

        $response->assertRedirect();
        $this->assertFalse((bool) $this->member->fresh()->is_active);
    }

    // ── Test 10: Deactivated user cannot access dashboard ─────────
    public function test_deactivated_user_cannot_access_dashboard(): void
    {
        $this->member->update(['is_active' => false]);

        $response = $this->actingAs($this->member)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->get('/dashboard');

        $response->assertStatus(403);
    }

    // ── Test 11: Admin can remove a member ────────────────────────
    public function test_admin_can_remove_member(): void
    {
        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->delete("/team/members/{$this->member->id}");

        $response->assertRedirect();
        $this->assertNull($this->member->fresh()->tenant_id);
    }

    // ── Test 12: Removed member cannot access dashboard ───────────
    public function test_removed_member_cannot_access_dashboard(): void
    {
        $this->member->update(['tenant_id' => null, 'is_active' => false]);

        $response = $this->actingAs($this->member)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->get('/dashboard');

        $response->assertStatus(403);
    }

    // ── Test 13: Cancel invitation works ─────────────────────────
    public function test_admin_can_cancel_invitation(): void
    {
        $invitation = TeamInvitation::create([
            'tenant_id'  => $this->tenant->id,
            'invited_by' => $this->admin->id,
            'email'      => 'cancel@test.com',
            'role'       => 'member',
            'token'      => TeamInvitation::generateToken(),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->delete("/team/invitations/{$invitation->id}");

        $response->assertRedirect();
        $this->assertEquals('expired', $invitation->fresh()->status);
    }

    // ── Test 14: Admin can add member to restricted project ────────
    public function test_admin_can_add_member_to_project(): void
    {
        $project = Project::create([
            'tenant_id'     => $this->tenant->id,
            'user_id'       => $this->admin->id,
            'name'          => 'Test Project',
            'model'         => 'llama3',
            'is_restricted' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->post("/projects/{$project->id}/members", [
                'user_id' => $this->member->id,
                'role'    => 'editor',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id'    => $this->member->id,
            'role'       => 'editor',
        ]);
    }

    // ── Test 15: API team list requires auth ──────────────────────
    public function test_api_team_requires_auth(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/team');

        $response->assertStatus(401);
    }

    // ── Test 16: API invite requires admin ────────────────────────
    public function test_api_invite_requires_admin(): void
    {
        $token = $this->member->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->postJson('/api/v1/team/invite', [
                'email' => 'someone@test.com',
                'role'  => 'member',
            ]);

        $response->assertStatus(403);
    }

    // ── Test 17: API team list returns members ────────────────────
    public function test_api_team_returns_members(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/team');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => ['members', 'pending_invitations', 'members_count'],
        ]);
    }

    // ── Test 18: TeamService generates unique tokens ───────────────
    public function test_team_service_generates_unique_tokens(): void
    {
        $t1 = TeamInvitation::generateToken();
        $t2 = TeamInvitation::generateToken();

        $this->assertNotEquals($t1, $t2);
        $this->assertEquals(64, strlen($t1));
    }

    // ── Test 19: Duplicate invitation refreshes token ─────────────
    public function test_duplicate_invitation_expires_old_one(): void
    {
        $ts = new TeamService();

        $inv1 = $ts->createInvitation($this->tenant, $this->admin, 'dup@test.com', 'member');
        $inv2 = $ts->createInvitation($this->tenant, $this->admin, 'dup@test.com', 'member');

        $this->assertEquals('expired', $inv1->fresh()->status);
        $this->assertEquals('pending', $inv2->fresh()->status);
    }

    // ── Test 20: Cross-tenant invitation token cannot be used ──────
    public function test_cannot_use_another_tenants_invitation(): void
    {
        $otherTenant = Tenant::create([
            'name'        => 'Other',
            'slug'        => 'other',
            'plan_id'     => $this->plan->id,
            'token_quota' => 100000,
            'tokens_used' => 0,
            'status'      => 'active',
        ]);

        $otherAdmin = User::create([
            'name'      => 'Other Admin',
            'email'     => 'other@test.com',
            'password'  => bcrypt('p'),
            'role'      => 'admin',
            'tenant_id' => $otherTenant->id,
            'is_active' => true,
        ]);

        $ts  = new TeamService();
        $inv = $ts->createInvitation($otherTenant, $otherAdmin, 'victim@test.com', 'admin');

        // actingAs admin from THIS tenant tries to cancel OTHER tenant's invite
        $response = $this->actingAs($this->admin)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->delete("/team/invitations/{$inv->id}");

        $response->assertStatus(403);
    }
}
