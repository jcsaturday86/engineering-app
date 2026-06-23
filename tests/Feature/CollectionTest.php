<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
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

    public function test_can_process_payment(): void
    {
        $app = $this->createBilledApplication();

        $response = $this->actingAs($this->admin)->post("/collections/{$app->id}/pay", [
            'or_number' => 'OR-TEST-001',
            'paid_by' => 'Test Payer',
            'amount_received' => 1000,
            'payment_mode' => 'cash',
        ]);

        $response->assertRedirect();
        $app->refresh();
        $this->assertEquals('paid', $app->status);
        $this->assertDatabaseHas('collections', ['or_number' => 'OR-TEST-001']);
    }

    public function test_cannot_duplicate_or_number(): void
    {
        $app1 = $this->createBilledApplication('BP-TEST-001');
        $app2 = $this->createBilledApplication('BP-TEST-002');

        $this->actingAs($this->admin)->post("/collections/{$app1->id}/pay", [
            'or_number' => 'OR-DUP-001',
            'paid_by' => 'Payer',
            'amount_received' => 1000,
            'payment_mode' => 'cash',
        ]);

        $response = $this->actingAs($this->admin)->post("/collections/{$app2->id}/pay", [
            'or_number' => 'OR-DUP-001',
            'paid_by' => 'Payer',
            'amount_received' => 1000,
            'payment_mode' => 'cash',
        ]);

        $response->assertSessionHasErrors('or_number');
    }

    private function createBilledApplication(string $appNumber = 'BP-TEST-001'): Application
    {
        $permitType = \App\Models\PermitType::where('code', 'BP')->first();
        $appType = \App\Models\ApplicationType::first();

        $app = Application::create([
            'permit_type_id' => $permitType->id,
            'application_type_id' => $appType->id,
            'app_year' => now()->year,
            'app_month' => now()->month,
            'app_counter' => 1,
            'application_number' => $appNumber,
            'status' => 'billed',
            'source' => 'walk_in',
            'applicant_first_name' => 'Test',
            'applicant_last_name' => 'User',
            'entered_by' => $this->admin->id,
        ]);

        $billing = Billing::create([
            'application_id' => $app->id,
            'billing_number' => 'BL-' . $appNumber,
            'total_amount' => 1000,
            'status' => 'unpaid',
            'generated_by' => $this->admin->id,
        ]);

        BillingItem::create([
            'billing_id' => $billing->id,
            'category' => 'CONST',
            'description' => 'Construction Fees',
            'amount' => 1000,
        ]);

        return $app;
    }
}
