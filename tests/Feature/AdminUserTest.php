<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'         => 'Bimbel Hebat',
            'slug'         => 'bimbel-hebat',
            'brand_color'  => '#F97316',
            'package_type' => 'pro',
            'status'       => 'active',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Bimbel Utama',
            'username'  => 'adminutama',
            'email'     => 'admin@bimbelhebat.com',
            'password'  => 'secret123',
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);
    }

    public function test_guest_cannot_access_admin_users(): void
    {
        $response = $this->get('/admin-users');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_admin_users_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin-users');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('AdminUser/Index')
                 ->has('users.data')
                 ->has('stats')
        );
    }

    public function test_admin_can_create_new_staff_user(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin-users', [
            'name'     => 'Staff Tata Usaha',
            'username' => 'stafftu',
            'email'    => 'tu@bimbelhebat.com',
            'phone'    => '081234567890',
            'role'     => 'staff',
            'password' => 'password123',
            'status'   => 'active',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Staff Tata Usaha',
            'username'  => 'stafftu',
            'role'      => 'staff',
            'status'    => 'active',
        ]);
    }

    public function test_validation_fails_when_username_already_exists(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin-users', [
            'name'     => 'Akun Kembar',
            'username' => 'adminutama',
            'email'    => 'lain@bimbelhebat.com',
            'role'     => 'staff',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_admin_can_update_user(): void
    {
        $targetUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Operator Lama',
            'username'  => 'operatorlama',
            'email'     => 'op@bimbelhebat.com',
            'role'      => 'staff',
            'password'  => 'password123',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->put("/admin-users/{$targetUser->id}", [
            'name'     => 'Operator Baru',
            'username' => 'operatorbaru',
            'email'    => 'opbaru@bimbelhebat.com',
            'role'     => 'admin_bimbel',
            'status'   => 'active',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id'       => $targetUser->id,
            'name'     => 'Operator Baru',
            'username' => 'operatorbaru',
            'role'     => 'admin_bimbel',
        ]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)->delete("/admin-users/{$this->adminUser->id}");

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', [
            'id'         => $this->adminUser->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_soft_delete_other_user(): void
    {
        $targetUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Staff Sementara',
            'username'  => 'staffsementara',
            'email'     => 'temp@bimbelhebat.com',
            'role'      => 'staff',
            'password'  => 'password123',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/admin-users/{$targetUser->id}");

        $response->assertSessionHas('success');
        $this->assertSoftDeleted('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $targetUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Staff Toggle',
            'username'  => 'stafftoggle',
            'email'     => 'toggle@bimbelhebat.com',
            'role'      => 'staff',
            'password'  => 'password123',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin-users/{$targetUser->id}/toggle-status");
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id'     => $targetUser->id,
            'status' => 'inactive',
        ]);
    }
}
