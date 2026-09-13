<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantLandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandingPageSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_landing_page_settings()
    {
        $response = $this->get('/landing-page-settings');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_landing_page_settings()
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel No Name',
            'slug'   => 'bimbel-no-name-' . Str::random(5),
            'phone'  => '081234567890',
            'city'   => 'Jakarta Selatan',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($user)->get('/landing-page-settings');
        $response->assertStatus(200);
    }

    public function test_admin_can_update_landing_page_settings()
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel No Name',
            'slug'   => 'bimbel-no-name-' . Str::random(5),
            'phone'  => '081234567890',
            'city'   => 'Jakarta Selatan',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $defaults = TenantLandingSetting::getDefaults();

        $updatedSlides = $defaults['hero_slides'];
        $updatedSlides[0]['title'] = 'Judul Slide Baru Yang Sangat Keren';

        $response = $this->actingAs($user)->post('/landing-page-settings', [
            'navbar_subtitle' => 'Subjudul Navbar Baru',
            'hero_slides'     => $updatedSlides,
            'quality_header'  => $defaults['quality_header'],
            'quality_items'   => $defaults['quality_items'],
            'parent_cta'      => $defaults['parent_cta'],
            'tentor_cta'      => $defaults['tentor_cta'],
            'contact_section' => $defaults['contact_section'],
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenant_landing_settings', [
            'tenant_id'       => $tenant->id,
            'navbar_subtitle' => 'Subjudul Navbar Baru',
        ]);

        $setting = TenantLandingSetting::where('tenant_id', $tenant->id)->first();
        $this->assertEquals('Judul Slide Baru Yang Sangat Keren', $setting->hero_slides[0]['title']);
    }

    public function test_admin_can_reset_landing_page_settings()
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel No Name',
            'slug'   => 'bimbel-no-name-' . Str::random(5),
            'phone'  => '081234567890',
            'city'   => 'Jakarta Selatan',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        // Create setting with custom values
        $setting = TenantLandingSetting::create([
            'tenant_id'       => $tenant->id,
            'navbar_subtitle' => 'Custom Tagline',
        ]);

        $response = $this->actingAs($user)->post('/landing-page-settings/reset');
        $response->assertSessionHas('success');

        $setting->refresh();
        $this->assertEquals('Standar Kualitas Bimbingan Belajar Modern', $setting->navbar_subtitle);
    }

    public function test_landing_page_renders_successfully()
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel No Name',
            'slug'   => 'bimbel-no-name',
            'phone'  => '081234567890',
            'city'   => 'Jakarta Selatan',
            'status' => 'active',
        ]);

        TenantLandingSetting::create([
            'tenant_id'       => $tenant->id,
            'navbar_subtitle' => 'Pusat Keunggulan Siswa',
        ]);

        // First hit (populates cache)
        $response1 = $this->get('/');
        $response1->assertStatus(200);

        // Second hit (reads from cache, tests unserialize/cache retrieval)
        $response2 = $this->get('/');
        $response2->assertStatus(200);
    }
}
