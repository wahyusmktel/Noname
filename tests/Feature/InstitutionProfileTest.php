<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstitutionProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_institution_profile()
    {
        $response = $this->get('/lembaga/profil');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_institution_profile()
    {
        $tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name-' . Str::random(5),
            'phone'        => '081234567890',
            'city'         => 'Jakarta Selatan',
            'status'       => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel No Name',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($user)->get('/lembaga/profil');
        $response->assertStatus(200);
    }

    public function test_admin_can_update_institution_profile()
    {
        $tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name-' . Str::random(5),
            'phone'        => '081234567890',
            'city'         => 'Jakarta Selatan',
            'status'       => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel No Name',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($user)->put('/lembaga/profil', [
            'name'             => 'Bimbel No Name Pusat',
            'tagline'          => 'Bimbingan Belajar Juara Berbasis Karakter',
            'description'      => 'Deskripsi lengkap bimbingan belajar...',
            'phone'            => '081299998888',
            'phone_2'          => '085711112222',
            'whatsapp_sender'  => '081299998888',
            'email'            => 'kontak@bimbelnoname.com',
            'website'          => 'https://bimbelnoname.com',
            'address'          => 'Jl. Margonda No. 100',
            'city'             => 'Jakarta Selatan',
            'province'         => 'DKI Jakarta',
            'postal_code'      => '12340',
            'operating_hours'  => 'Senin - Sabtu (08:00 - 20:00 WIB)',
            'brand_color'      => '#F97316',
            'tiktok_url'       => 'https://www.tiktok.com/@bimbelnoname',
            'instagram_url'    => 'https://www.instagram.com/bimbelnoname',
            'youtube_url'      => 'https://www.youtube.com/@bimbelnoname',
            'facebook_url'     => 'https://www.facebook.com/bimbelnoname',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [
            'id'               => $tenant->id,
            'name'             => 'Bimbel No Name Pusat',
            'tagline'          => 'Bimbingan Belajar Juara Berbasis Karakter',
            'phone'            => '081299998888',
            'phone_2'          => '085711112222',
            'whatsapp_sender'  => '081299998888',
            'city'             => 'Jakarta Selatan',
            'tiktok_url'       => 'https://www.tiktok.com/@bimbelnoname',
            'instagram_url'    => 'https://www.instagram.com/bimbelnoname',
            'youtube_url'      => 'https://www.youtube.com/@bimbelnoname',
            'facebook_url'     => 'https://www.facebook.com/bimbelnoname',
        ]);
    }
}
