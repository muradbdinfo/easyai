<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User   $userA;
    private User   $userB;
    private Plan   $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name'                => 'Starter',
            'monthly_token_limit' => 500000,
            'price'               => 29.00,
            'features'            => [],
            'is_active'           => true,
        ]);

        $this->tenantA = Tenant::create([
            'name' => 'A', 'slug' => 'a',
            'plan_id' => $this->plan->id,
            'token_quota' => 500000, 'tokens_used' => 0,
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'B', 'slug' => 'b',
            'plan_id' => $this->plan->id,
            'token_quota' => 500000, 'tokens_used' => 0,
            'status' => 'active',
        ]);

        $this->userA = User::create([
            'name' => 'User A', 'email' => 'a@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin', 'tenant_id' => $this->tenantA->id,
        ]);

        $this->userB = User::create([
            'name' => 'User B', 'email' => 'b@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin', 'tenant_id' => $this->tenantB->id,
        ]);
    }

    // ── Cannot update another tenant's project ────────────────────
    public function test_cannot_update_other_tenant_project(): void
    {
        $projectB = Project::create([
            'tenant_id' => $this->tenantB->id,
            'user_id'   => $this->userB->id,
            'name'      => 'B Project',
            'model'     => 'llama3',
        ]);

        $response = $this->actingAs($this->userA)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->put("/projects/{$projectB->id}", [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('projects', ['name' => 'Hacked']);
    }

    // ── Cannot delete another tenant's project ────────────────────
    public function test_cannot_delete_other_tenant_project(): void
    {
        $projectB = Project::create([
            'tenant_id' => $this->tenantB->id,
            'user_id'   => $this->userB->id,
            'name'      => 'B Project',
            'model'     => 'llama3',
        ]);

        $response = $this->actingAs($this->userA)
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->delete("/projects/{$projectB->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $projectB->id]);
    }

    // ── API cannot access another tenant's project ────────────────
    public function test_api_cannot_access_other_tenant_project(): void
    {
        $projectB = Project::create([
            'tenant_id' => $this->tenantB->id,
            'user_id'   => $this->userB->id,
            'name'      => 'B Project',
            'model'     => 'llama3',
        ]);

        $tokenA = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $tokenA,
                'Accept'        => 'application/json',
            ])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson("/api/v1/projects/{$projectB->id}");

        $response->assertStatus(403);
    }

    // ── Cannot create project with another tenant_id ──────────────
    public function test_project_tenant_id_always_from_middleware(): void
    {
        $tokenA = $this->userA->createToken('test')->plainTextToken;

        // Attempt to inject tenantB's id in body
        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $tokenA,
                'Accept'        => 'application/json',
            ])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->postJson('/api/v1/projects', [
                'name'      => 'Injected Project',
                'tenant_id' => $this->tenantB->id, // attempted injection
                'model'     => 'llama3',
            ]);

        // Project must be created under tenantA, not tenantB
        if ($response->status() === 201 || $response->status() === 200) {
            $this->assertDatabaseMissing('projects', [
                'name'      => 'Injected Project',
                'tenant_id' => $this->tenantB->id,
            ]);
        }
    }

    // ── Unauthenticated API request returns 401 ───────────────────
    public function test_unauthenticated_api_returns_401(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/projects');

        $response->assertStatus(401);
    }
}