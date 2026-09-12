<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name',
            'brand_color'  => '#F97316',
            'package_type' => 'pro',
            'status'       => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Bimbel',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => bcrypt('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);
    }

    public function test_guest_cannot_access_subjects(): void
    {
        $response = $this->get('/subjects');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_subjects_index(): void
    {
        Subject::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Matematika Wajib',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/subjects');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Subject/Index')
                 ->has('subjects.data', 1)
                 ->where('stats.total', 1)
                 ->where('stats.active', 1)
                 ->where('stats.inactive', 0)
        );
    }

    public function test_user_can_create_new_subject(): void
    {
        $response = $this->actingAs($this->user)->post('/subjects', [
            'name'      => 'Fisika Kuantum',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Fisika Kuantum',
            'is_active' => 1,
        ]);
    }

    public function test_subject_creation_validation(): void
    {
        $response = $this->actingAs($this->user)->post('/subjects', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_update_subject(): void
    {
        $subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Kimia Dasar',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put("/subjects/{$subject->id}", [
            'name'      => 'Kimia Analitik Lanjutan',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', [
            'id'        => $subject->id,
            'name'      => 'Kimia Analitik Lanjutan',
            'is_active' => 0,
        ]);
    }

    public function test_user_can_soft_delete_subject(): void
    {
        $subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Biologi Terapan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->delete("/subjects/{$subject->id}");
        $response->assertRedirect();

        $this->assertSoftDeleted('subjects', [
            'id' => $subject->id,
        ]);
    }

    public function test_multi_tenant_isolation_for_subjects(): void
    {
        // Tenant lain
        $otherTenant = Tenant::create([
            'name'         => 'Bimbel Bintang Prestasi',
            'slug'         => 'bintang-prestasi',
            'brand_color'  => '#3B82F6',
            'package_type' => 'pro',
            'status'       => 'active',
        ]);

        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name'      => 'Admin Bintang',
            'email'     => 'admin@bintangprestasi.com',
            'password'  => bcrypt('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        // Subject tenant lain
        $otherSubject = Subject::create([
            'tenant_id' => $otherTenant->id,
            'name'      => 'Mata Pelajaran Rahasia Bintang',
            'is_active' => true,
        ]);

        // Subject tenant No Name
        Subject::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Matematika No Name',
            'is_active' => true,
        ]);

        // Login sebagai No Name, pastikan TIDAK bisa melihat subject tenant Bintang
        $response = $this->actingAs($this->user)->get('/subjects');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Subject/Index')
                 ->has('subjects.data', 1)
                 ->where('subjects.data.0.name', 'Matematika No Name')
        );

        // Pastikan juga Tentor controller memuat daftar subject aktif
        $tentorResponse = $this->actingAs($this->user)->get('/tentors');
        $tentorResponse->assertStatus(200);
        $tentorResponse->assertInertia(fn ($page) =>
            $page->component('Tentor/Index')
                 ->has('subjects', 1)
                 ->where('subjects.0.name', 'Matematika No Name')
        );
    }
}
