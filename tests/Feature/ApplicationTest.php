<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\ReferenceDataSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super-admin');
    }

    public function test_application_list_requires_auth(): void
    {
        $response = $this->get('/applications');
        $response->assertRedirect('/login');
    }

    public function test_application_list_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/applications');
        $response->assertStatus(200);
    }

    public function test_create_form_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/applications/create?type=BP');
        $response->assertStatus(200);
    }

    public function test_can_create_building_permit_application(): void
    {
        $permitType = \App\Models\PermitType::where('code', 'BP')->first();
        $appType = \App\Models\ApplicationType::first();

        $response = $this->actingAs($this->admin)->post('/applications', [
            'permit_type_id' => $permitType->id,
            'application_type_id' => $appType->id,
            'applicant_first_name' => 'Test',
            'applicant_last_name' => 'User',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'applicant_first_name' => 'Test',
            'applicant_last_name' => 'User',
            'status' => 'draft',
        ]);
    }

    public function test_can_submit_draft_application(): void
    {
        $app = $this->createTestApplication();

        $response = $this->actingAs($this->admin)->post("/applications/{$app->id}/submit");
        $response->assertRedirect();

        $app->refresh();
        $this->assertEquals('submitted', $app->status);
    }

    public function test_cannot_submit_non_draft_application(): void
    {
        $app = $this->createTestApplication('submitted');

        $response = $this->actingAs($this->admin)->post("/applications/{$app->id}/submit");
        $response->assertSessionHas('error');
    }

    public function test_can_cancel_application(): void
    {
        $app = $this->createTestApplication();

        $response = $this->actingAs($this->admin)->post("/applications/{$app->id}/cancel", [
            'reason' => 'Test cancellation',
        ]);

        $app->refresh();
        $this->assertEquals('cancelled', $app->status);
    }

    public function test_unauthorized_user_cannot_access_applications(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('client');

        $response = $this->actingAs($user)->get('/applications');
        $response->assertStatus(403);
    }

    private function createTestApplication(string $status = 'draft'): Application
    {
        $permitType = \App\Models\PermitType::where('code', 'BP')->first();
        $appType = \App\Models\ApplicationType::first();

        return Application::create([
            'permit_type_id' => $permitType->id,
            'application_type_id' => $appType->id,
            'app_year' => now()->year,
            'app_month' => now()->month,
            'app_counter' => 1,
            'application_number' => 'BP-TEST-001',
            'status' => $status,
            'source' => 'walk_in',
            'applicant_first_name' => 'Test',
            'applicant_last_name' => 'User',
            'entered_by' => $this->admin->id,
        ]);
    }
}
