<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Student;
use App\Models\Tentor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserProfileAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Bimbel Hebat',
            'slug'   => 'bimbel-hebat-' . Str::random(4),
            'phone'  => '081234567890',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Utama',
            'email'     => 'admin@bimbelhebat.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);
    }

    public function test_guest_cannot_access_profile_settings_help()
    {
        $this->get('/user/profile')->assertRedirect('/login');
        $this->get('/user/settings')->assertRedirect('/login');
        $this->get('/help-center')->assertRedirect('/login');
    }

    public function test_user_can_view_profile()
    {
        $response = $this->actingAs($this->admin)->get('/user/profile');
        $response->assertStatus(200);
    }

    public function test_user_can_upload_and_delete_photo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($this->admin)->post('/user/profile/photo', [
            'photo' => $file,
        ]);

        $response->assertSessionHas('success');
        $this->admin->refresh();
        $this->assertNotNull($this->admin->avatar);
        Storage::disk('public')->assertExists($this->admin->avatar);

        // Delete photo
        $deleteResponse = $this->actingAs($this->admin)->delete('/user/profile/photo');
        $deleteResponse->assertSessionHas('success');
        $this->admin->refresh();
        $this->assertNull($this->admin->avatar);
    }

    public function test_user_can_view_settings()
    {
        $response = $this->actingAs($this->admin)->get('/user/settings');
        $response->assertStatus(200);
    }

    public function test_user_can_update_password()
    {
        $response = $this->actingAs($this->admin)->put('/user/settings/password', [
            'current_password'      => 'password123',
            'password'              => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ]);

        $response->assertSessionHas('success');
        $this->admin->refresh();
        $this->assertTrue(Hash::check('NewSecurePass2026!', $this->admin->password));
    }

    public function test_update_password_fails_if_current_password_incorrect()
    {
        $response = $this->actingAs($this->admin)->put('/user/settings/password', [
            'current_password'      => 'wrongpassword',
            'password'              => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_roles_can_access_help_center()
    {
        // Admin
        $this->actingAs($this->admin)->get('/help-center')->assertStatus(200);

        // Tutor
        $tutorUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Tentor Budi',
            'email'     => 'budi@bimbelhebat.com',
            'password'  => Hash::make('password123'),
            'role'      => 'tutor',
            'status'    => 'active',
        ]);
        $this->actingAs($tutorUser)->get('/help-center')->assertStatus(200);

        // Siswa
        $studentUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Siswa Andi',
            'email'     => 'andi@bimbelhebat.com',
            'password'  => Hash::make('password123'),
            'role'      => 'siswa',
            'status'    => 'active',
        ]);
        $this->actingAs($studentUser)->get('/help-center')->assertStatus(200);
    }
}
