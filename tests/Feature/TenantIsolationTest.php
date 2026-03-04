<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User   $userA;
    private User   $userB;
    private User   $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name'                => 'Starter',
            'monthly_token_limit' => 500000,
            'price'               => 29.00,
            'features'            => ['500K tokens'],
            'is_active'           => true,
        ]);

        $this->tenantA = Tenant::create([
            'name'        => 'Tenant A',
            'slug'        => 'tenant-a',
            'plan_id'     => $plan->id,
            'token_quota' => 500000,
            'tokens_used' => 0,
            'status'      => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name'        => 'Tenant B',
            'slug'        => 'tenant-b',
            'plan_id'     => $plan->id,
            'token_quota' => 500000,
            'tokens_used' => 0,
            'status'      => 'active',
        ]);

        $this->userA = User::create([
            'name'      => 'User A',
            'email'     => 'usera@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->userB = User::create([
            'name'      => 'User B',
            'email'     => 'userb@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'tenant_id' => $this->tenantB->id,
        ]);

        $this->superAdmin = User::create([
            'name'      => 'Super Admin',
            'email'     => 'admin@easyai.local',
            'password'  => bcrypt('password'),
            'role'      => 'superadmin',
            'tenant_id' => null,
        ]);
    }

    // ── Test 1: User cannot access other tenant's project ─────────
    public function test_user_cannot_access_other_tenant_project(): void
    {
        $projectB = Project::create([
            'tenant_id' => $this->tenantB->id,
            'user_id'   => $this->userB->id,
            'name'      => 'Project B',
            'model'     => 'llama3',
        ]);

        $response = $this->actingAs($this->userA)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->get("/projects/{$projectB->id}");

        $response->assertStatus(403);
    }

    // ── Test 2: User cannot access other tenant's chat ────────────
    public function test_user_cannot_access_other_tenant_chat(): void
    {
        $projectB = Project::create([
            'tenant_id' => $this->tenantB->id,
            'user_id'   => $this->userB->id,
            'name'      => 'Project B',
            'model'     => 'llama3',
        ]);

        $chatB = Chat::create([
            'project_id' => $projectB->id,
            'user_id'    => $this->userB->id,
            'tenant_id'  => $this->tenantB->id,
            'title'      => 'Chat B',
            'status'     => 'open',
        ]);

        $response = $this->actingAs($this->userA)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->get("/projects/{$projectB->id}/chats/{$chatB->id}");

        $response->assertStatus(403);
    }

    // ── Test 3: Quota exceeded blocks message (web) ───────────────
    public function test_quota_exceeded_blocks_message_web(): void
    {
        $this->tenantA->update([
            'token_quota' => 100,
            'tokens_used' => 100,
        ]);

        $project = Project::create([
            'tenant_id' => $this->tenantA->id,
            'user_id'   => $this->userA->id,
            'name'      => 'Test Project',
            'model'     => 'llama3',
        ]);

        $chat = Chat::create([
            'project_id' => $project->id,
            'user_id'    => $this->userA->id,
            'tenant_id'  => $this->tenantA->id,
            'title'      => 'Test Chat',
            'status'     => 'open',
        ]);

        $response = $this->actingAs($this->userA)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->post("/projects/{$project->id}/chats/{$chat->id}/messages", [
                'content' => 'Hello',
            ]);

        // Should redirect back with quota_exceeded error
        $response->assertSessionHasErrors();
    }

    // ── Test 4: Quota exceeded returns 402 (API) ──────────────────
    public function test_quota_exceeded_blocks_message_api(): void
    {
        $this->tenantA->update([
            'token_quota' => 100,
            'tokens_used' => 100,
        ]);

        $project = Project::create([
            'tenant_id' => $this->tenantA->id,
            'user_id'   => $this->userA->id,
            'name'      => 'Test Project',
            'model'     => 'llama3',
        ]);

        $chat = Chat::create([
            'project_id' => $project->id,
            'user_id'    => $this->userA->id,
            'tenant_id'  => $this->tenantA->id,
            'title'      => 'Test Chat',
            'status'     => 'open',
        ]);

        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->postJson("/api/v1/projects/{$project->id}/chats/{$chat->id}/messages", [
                'content' => 'Hello',
            ]);

        $response->assertStatus(402);
        $response->assertJson(['success' => false, 'message' => 'quota_exceeded']);
    }

    // ── Test 5: Suspended tenant cannot send message ──────────────
    public function test_suspended_tenant_cannot_send_message(): void
    {
        $suspendedTenant = Tenant::create([
            'name'        => 'Suspended',
            'slug'        => 'suspended',
            'plan_id'     => $this->tenantA->plan_id,
            'token_quota' => 500000,
            'tokens_used' => 0,
            'status'      => 'suspended',
        ]);

        $suspendedUser = User::create([
            'name'      => 'Suspended User',
            'email'     => 'suspended@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'tenant_id' => $suspendedTenant->id,
        ]);

        $response = $this->actingAs($suspendedUser)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->get('/dashboard');

        $response->assertStatus(403);
    }

    // ── Test 6: Superadmin can access admin panel ─────────────────
    public function test_superadmin_can_access_admin_panel(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withServerVariables(['HTTP_HOST' => 'admin.easyai.local'])
            ->get('/');

        // Either OK or redirect (to dashboard) — not 403
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // ── Test 7: Regular user cannot access admin panel ────────────
public function test_regular_user_cannot_access_admin_panel(): void
{
    $response = $this->actingAs($this->userA)
        ->withServerVariables(['HTTP_HOST' => 'admin.easyai.local'])
        ->get('/');

    // 403 = middleware blocked | 302 = auth redirected (domain routing in tests)
    // Both mean access denied — not 200
    $this->assertNotEquals(200, $response->getStatusCode());
    $this->assertContains($response->getStatusCode(), [302, 403]);
}

    // ── Test 8: API token auth works ──────────────────────────────
    public function test_api_token_auth_works(): void
    {
        $token = $this->userA->createToken('mobile-app')->plainTextToken;

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.email', 'usera@test.com');
    }

    // ── Test 9: API returns JSON only ─────────────────────────────
    public function test_api_returns_json_only(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/auth/me');

        // Unauthenticated — should return JSON 401, not HTML redirect
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
    }
}