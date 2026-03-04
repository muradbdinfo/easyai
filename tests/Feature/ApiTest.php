<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User   $user;
    private Plan   $plan;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name'                => 'Pro',
            'monthly_token_limit' => 2000000,
            'price'               => 79.00,
            'features'            => ['2M tokens'],
            'is_active'           => true,
        ]);

        $this->tenant = Tenant::create([
            'name'        => 'Test Tenant',
            'slug'        => 'test-tenant',
            'plan_id'     => $this->plan->id,
            'token_quota' => 2000000,
            'tokens_used' => 0,
            'status'      => 'active',
        ]);

        $this->user = User::create([
            'name'      => 'Test User',
            'email'     => 'test@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
        ];
    }

    // ── Test 1: Login returns token ───────────────────────────────
    public function test_auth_login_returns_token(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->postJson('/api/v1/auth/login', [
                'email'    => 'test@test.com',
                'password' => 'password',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => ['token', 'user'],
        ]);
    }

    // ── Test 2: Me returns user with tenant ───────────────────────
    public function test_auth_me_returns_user_with_tenant(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'role', 'tenant'],
        ]);
        $response->assertJsonPath('data.tenant.id', $this->tenant->id);
    }

    // ── Test 3: Projects API scoped to tenant ─────────────────────
    public function test_projects_api_scoped_to_tenant(): void
    {
        // Create project for THIS tenant
        Project::create([
            'tenant_id' => $this->tenant->id,
            'user_id'   => $this->user->id,
            'name'      => 'My Project',
            'model'     => 'llama3',
        ]);

        // Create another tenant + project (should NOT appear)
        $otherTenant = Tenant::create([
            'name'        => 'Other',
            'slug'        => 'other',
            'plan_id'     => $this->plan->id,
            'token_quota' => 500000,
            'tokens_used' => 0,
            'status'      => 'active',
        ]);

        $otherUser = User::create([
            'name'      => 'Other',
            'email'     => 'other@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'tenant_id' => $otherTenant->id,
        ]);

        Project::create([
            'tenant_id' => $otherTenant->id,
            'user_id'   => $otherUser->id,
            'name'      => 'Other Project',
            'model'     => 'llama3',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/projects');

        $response->assertStatus(200);

        // Only 1 project should be returned (not other tenant's)
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('My Project', $response->json('data.0.name'));
    }

    // ── Test 4: Messages API dispatches job ───────────────────────
    public function test_messages_api_dispatches_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'tenant_id' => $this->tenant->id,
            'user_id'   => $this->user->id,
            'name'      => 'Test Project',
            'model'     => 'llama3',
        ]);

        $chat = Chat::create([
            'project_id' => $project->id,
            'user_id'    => $this->user->id,
            'tenant_id'  => $this->tenant->id,
            'title'      => 'Test Chat',
            'status'     => 'open',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->postJson(
                "/api/v1/projects/{$project->id}/chats/{$chat->id}/messages",
                ['content' => 'Hello EasyAI']
            );

        $response->assertStatus(202);
        $response->assertJson(['success' => true]);

        Queue::assertPushed(\App\Jobs\SendMessageJob::class);
    }

    // ── Test 5: Quota exceeded returns 402 ───────────────────────
    public function test_quota_exceeded_returns_402(): void
    {
        $this->tenant->update([
            'token_quota' => 10,
            'tokens_used' => 10,
        ]);

        $project = Project::create([
            'tenant_id' => $this->tenant->id,
            'user_id'   => $this->user->id,
            'name'      => 'Test Project',
            'model'     => 'llama3',
        ]);

        $chat = Chat::create([
            'project_id' => $project->id,
            'user_id'    => $this->user->id,
            'tenant_id'  => $this->tenant->id,
            'title'      => 'Test Chat',
            'status'     => 'open',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->postJson(
                "/api/v1/projects/{$project->id}/chats/{$chat->id}/messages",
                ['content' => 'Hello']
            );

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'message' => 'quota_exceeded',
        ]);
    }

    // ── Test 6: Billing plans API returns plans ───────────────────
    public function test_billing_plans_api_returns_plans(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->withServerVariables(['HTTP_HOST' => 'easyai.local'])
            ->getJson('/api/v1/billing/plans');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('data'));

        // Verify plan structure
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'monthly_token_limit', 'price'],
            ],
        ]);
    }
}