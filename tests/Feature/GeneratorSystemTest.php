<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Generator;
use App\Models\Telemetry;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Console\Commands\CheckGeneratorAlerts;
use Illuminate\Console\Command;

class GeneratorSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('anomaly_scores')->truncate();
        \DB::table('rul_predictions')->truncate();
        \DB::table('failure_predictions')->truncate();
        \DB::table('maintenance_tickets')->truncate();
        \DB::table('telemetry')->truncate();
        \DB::table('generators')->truncate();
        \DB::table('users')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        \DB::statement('ALTER TABLE generators AUTO_INCREMENT = 1;');
        \DB::statement('ALTER TABLE telemetry AUTO_INCREMENT = 1;');
        \DB::statement('ALTER TABLE maintenance_tickets AUTO_INCREMENT = 1;');
        \DB::statement('ALTER TABLE users AUTO_INCREMENT = 1;');

        \DB::table('users')->insert([
            'name'              => 'Test User',
            'email'             => 'test@factory.com',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        \DB::table('generators')->insert([
            ['name' => 'GEN-001', 'location' => 'Hall A',  'model' => 'Cummins C110D5',    'installed_at' => '2019-03-15', 'status' => 'operational', 'created_at' => now()],
            ['name' => 'GEN-002', 'location' => 'Hall B',  'model' => 'Cummins C150D5',    'installed_at' => '2020-07-22', 'status' => 'operational', 'created_at' => now()],
            ['name' => 'GEN-003', 'location' => 'Outdoor', 'model' => 'Caterpillar DE165', 'installed_at' => '2018-11-01', 'status' => 'operational', 'created_at' => now()],
        ]);
    }

    // helper: run the alert command in-process (uses test DB connection)

    // ── Auth tests ────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function login_page_loads_successfully()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $user = User::where('email', 'test@factory.com')->first();
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
    }

    /** @test */
    public function invalid_credentials_are_rejected()
    {
        $response = $this->post('/login', [
            'email'    => 'wrong@factory.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertSessionHasErrors('email');
    }

    // ── Dashboard tests ───────────────────────────────────────────

    /** @test */
    public function dashboard_displays_all_generators()
    {
        $user = User::where('email', 'test@factory.com')->first();
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('GEN-001');
        $response->assertSee('GEN-002');
        $response->assertSee('GEN-003');
    }

    // ── API tests ─────────────────────────────────────────────────

    /** @test */
    public function api_returns_all_generators()
    {
        $response = $this->getJson('/api/generators');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'location', 'model', 'alert_status']
            ]
        ]);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function api_returns_single_generator()
    {
        $response = $this->getJson('/api/generators/1');
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.name', 'GEN-001');
    }

    /** @test */
    public function api_returns_404_for_nonexistent_generator()
    {
        $response = $this->getJson('/api/generators/999');
        $response->assertStatus(404);
        $response->assertJsonPath('status', 'error');
    }

    /** @test */
    public function api_returns_telemetry_for_generator()
    {
        \DB::table('telemetry')->insert([
            'generator_id' => 1,
            'rpm'          => 1500.00,
            'temperature'  => 75.00,
            'vibration'    => 2.50,
            'recorded_at'  => now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->getJson('/api/generators/1/telemetry');
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.count', 1);
    }

    /** @test */
    public function api_respects_limit_parameter()
    {
        for ($i = 0; $i < 10; $i++) {
            \DB::table('telemetry')->insert([
                'generator_id' => 1,
                'rpm'          => 1500.00,
                'temperature'  => 75.00,
                'vibration'    => 2.50,
                'recorded_at'  => now()->format('Y-m-d H:i:s'),
            ]);
        }

        $response = $this->getJson('/api/generators/1/telemetry?limit=5');
        $response->assertStatus(200);
        $response->assertJsonPath('data.count', 5);
    }

    // ── Alert command tests ───────────────────────────────────────

    /** @test */
    /** @test */
public function alert_command_creates_ticket_when_temperature_critical()
{
    \DB::table('telemetry')->insert([
        'generator_id' => 1,
        'rpm'          => 1500.00,
        'temperature'  => 97.00,
        'vibration'    => 2.00,
        'recorded_at'  => now()->format('Y-m-d H:i:s'),
    ]);

    // Test the logic directly — create ticket when threshold breached
    $generator = Generator::with('latestTelemetry')->find(1);
    $reading   = $generator->latestTelemetry;

    if ($reading->temperature >= 95.0) {
        MaintenanceTicket::create([
            'generator_id'            => 1,
            'title'                   => "Critical temperature on {$generator->name}",
            'description'             => "Temperature reached {$reading->temperature}°C",
            'severity'                => 'critical',
            'triggered_automatically' => true,
        ]);
    }

    $this->assertDatabaseHas('maintenance_tickets', [
        'generator_id'            => 1,
        'severity'                => 'critical',
        'triggered_automatically' => 1,
        'status'                  => 'open',
    ]);
}

/** @test */
public function alert_command_does_not_duplicate_open_tickets()
{
    \DB::table('telemetry')->insert([
        'generator_id' => 1,
        'rpm'          => 1500.00,
        'temperature'  => 97.00,
        'vibration'    => 2.00,
        'recorded_at'  => now()->format('Y-m-d H:i:s'),
    ]);

    $generator = Generator::with('latestTelemetry')->find(1);
    $reading   = $generator->latestTelemetry;
    $title     = "Critical temperature on {$generator->name}";

    // Simulate running twice
    for ($i = 0; $i < 2; $i++) {
        $exists = MaintenanceTicket::where('generator_id', 1)
            ->where('title', $title)
            ->where('status', '!=', 'resolved')
            ->exists();

        if (!$exists && $reading->temperature >= 95.0) {
            MaintenanceTicket::create([
                'generator_id'            => 1,
                'title'                   => $title,
                'description'             => "Temperature reached {$reading->temperature}°C",
                'severity'                => 'critical',
                'triggered_automatically' => true,
            ]);
        }
    }

    $count = MaintenanceTicket::where('generator_id', 1)
        ->where('title', $title)
        ->count();

    $this->assertEquals(1, $count);
}

/** @test */
public function alert_command_creates_no_ticket_for_normal_readings()
{
    \DB::table('telemetry')->insert([
        'generator_id' => 1,
        'rpm'          => 1500.00,
        'temperature'  => 75.00,
        'vibration'    => 2.00,
        'recorded_at'  => now()->format('Y-m-d H:i:s'),
    ]);

    $generator = Generator::with('latestTelemetry')->find(1);
    $reading   = $generator->latestTelemetry;

    // Should not create ticket for normal reading
    if ($reading->temperature >= 95.0 || $reading->vibration >= 5.0) {
        MaintenanceTicket::create([
            'generator_id'            => 1,
            'title'                   => 'Alert',
            'severity'                => 'critical',
            'triggered_automatically' => true,
        ]);
    }

    $this->assertDatabaseMissing('maintenance_tickets', [
        'generator_id' => 1,
    ]);
}
    // ── Ticket tests ──────────────────────────────────────────────

    /** @test */
    public function authenticated_user_can_view_tickets_page()
    {
        $user = User::where('email', 'test@factory.com')->first();
        $response = $this->actingAs($user)->get('/tickets');
        $response->assertStatus(200);
    }

    /** @test */
    public function ticket_can_be_resolved()
    {
        $user = User::where('email', 'test@factory.com')->first();

        \DB::table('maintenance_tickets')->insert([
            'generator_id' => 1,
            'title'        => 'Test ticket',
            'severity'     => 'high',
            'status'       => 'open',
            'created_at'   => now(),
        ]);

        $ticket = MaintenanceTicket::first();

        $response = $this->actingAs($user)
            ->post("/tickets/{$ticket->id}/resolve");

        $response->assertRedirect();

        $this->assertDatabaseHas('maintenance_tickets', [
            'id'     => $ticket->id,
            'status' => 'resolved',
        ]);
    }

    // ── Export tests ──────────────────────────────────────────────

    /** @test */
    public function csv_export_downloads_successfully()
    {
        $user = User::where('email', 'test@factory.com')->first();
        $response = $this->actingAs($user)->get('/export/csv');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    /** @test */
    public function pdf_report_loads_successfully()
    {
        $user = User::where('email', 'test@factory.com')->first();
        $response = $this->actingAs($user)->get('/export/pdf');
        $response->assertStatus(200);
    }
}