<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'id'    => (string) \Illuminate\Support\Str::uuid(),
            'name'  => 'Bimbel No name',
            'slug'  => 'bimbel-no-name',
            'email' => 'admin@bimbelnoname.com',
            'phone' => '081234567890',
        ]);

        $this->admin = User::create([
            'id'        => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'name'      => 'Super Admin',
            'email'     => 'superadmin@bimbelnoname.com',
            'password'  => bcrypt('password123'),
            'role'      => 'admin_bimbel',
        ]);
    }

    protected function tearDown(): void
    {
        $statusFile = storage_path('framework/dev_status.json');
        if (file_exists($statusFile)) {
            @unlink($statusFile);
        }
        parent::tearDown();
    }

    public function test_admin_can_view_dev_environment_control_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/system/dev-environment');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('System/DevEnvironment')
            ->has('status.is_active')
            ->has('dev_url')
        );
    }

    public function test_admin_can_toggle_dev_status_to_inactive(): void
    {
        $response = $this->actingAs($this->admin)->post('/system/dev-environment/toggle', [
            'is_active' => false,
            'message'   => 'Website dev sedang dalam pemeliharaan berkala.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $setting = SystemSetting::get('dev_environment_status');
        $this->assertIsArray($setting);
        $this->assertFalse($setting['active']);
        $this->assertEquals('Website dev sedang dalam pemeliharaan berkala.', $setting['message']);

        $statusFile = storage_path('framework/dev_status.json');
        $this->assertFileExists($statusFile);
        $fileData = json_decode(file_get_contents($statusFile), true);
        $this->assertFalse($fileData['active']);
    }

    public function test_dev_environment_returns_503_when_inactive(): void
    {
        // Tulis flag nonaktif
        $statusFile = storage_path('framework/dev_status.json');
        file_put_contents($statusFile, json_encode([
            'active'  => false,
            'message' => 'Dev sedang dinonaktifkan.',
        ]));

        // Simulasikan request ke host dev.bimbelnoname.com
        $response = $this->get('https://dev.bimbelnoname.com/');

        $response->assertStatus(503);
        $this->assertStringContainsString('Website Development Sedang Tidak Aktif', $response->getContent());
    }

    public function test_dev_environment_allows_bypass_token_when_inactive(): void
    {
        // Tulis flag nonaktif
        $statusFile = storage_path('framework/dev_status.json');
        file_put_contents($statusFile, json_encode([
            'active'  => false,
            'message' => 'Dev sedang dinonaktifkan.',
        ]));

        // Simulasikan request dengan token bypass
        $response = $this->get('https://dev.bimbelnoname.com/?bypass_dev=bnn_dev_bypass_2026');

        $response->assertStatus(200);
    }
}
