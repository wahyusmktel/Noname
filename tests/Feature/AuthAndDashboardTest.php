<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthAndDashboardTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
    public function test_landing_page_renders_with_slider_and_stats()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_login_page_renders_properly()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_user_can_login_and_redirect_to_dashboard()
    {
        $tenant = Tenant::create([
            'name'         => 'Bimbel Hebat',
            'slug'         => 'bimbel-hebat-' . Str::random(5),
            'email'        => 'hebat@test.com',
            'phone'        => '08123456789',
            'status'       => 'active',
        ]);

        $email = 'admin_' . Str::random(5) . '@hebat.com';
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Hebat',
            'email'     => $email,
            'password'  => Hash::make('secret123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $response = $this->post('/login', [
            'email'    => $email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_new_institution_wizard_registration()
    {
        $slug = 'bimbel-baru-' . Str::random(5);
        $email = 'admin_' . Str::random(5) . '@baru.com';

        $response = $this->post('/register', [
            'institution_name'      => 'Bimbel Juara Edu',
            'slug'                  => $slug,
            'city'                  => 'Surabaya',
            'phone'                 => '081399887766',
            'address'               => 'Jl. Pahlawan No. 45',
            'name'                  => 'Bambang Sudibyo',
            'email'                 => $email,
            'admin_phone'           => '081399887766',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'package_type'          => 'trial',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tenants', [
            'slug' => strtolower($slug),
            'name' => 'Bimbel Juara Edu',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role'  => 'admin_bimbel',
        ]);

        $createdTenant = Tenant::where('slug', strtolower($slug))->first();
        $this->assertNotNull($createdTenant);
        $this->assertTrue(Str::isUuid($createdTenant->id));

        $createdUser = User::where('email', $email)->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue(Str::isUuid($createdUser->id));
        $this->assertEquals($createdTenant->id, $createdUser->tenant_id);
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'bintang-prestasi'],
            [
                'name'         => 'Bimbel Bintang Prestasi',
                'phone'        => '081234567890',
                'status'       => 'active',
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'admin@bintangprestasi.com'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Budi Pratama, S.Pd.',
                'password'  => Hash::make('password123'),
                'role'      => 'admin_bimbel',
                'status'    => 'active',
            ]
        );

        $academicYear = \App\Models\AcademicYear::create([
            'tenant_id'  => $tenant->id,
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'is_active'  => true,
        ]);

        $group = \App\Models\StudyGroup::create([
            'tenant_id'        => $tenant->id,
            'academic_year_id' => $academicYear->id,
            'name'             => 'Kelas 10 Reguler',
            'is_active'        => true,
        ]);

        $student = \App\Models\Student::create([
            'tenant_id'        => $tenant->id,
            'academic_year_id' => $academicYear->id,
            'study_group_id'   => $group->id,
            'name'             => 'Dina Anjani',
            'username'         => '261050',
            'status'           => 'active',
        ]);

        $tentor = \App\Models\Tentor::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Budi Pratama, S.Pd.',
            'status'    => 'active',
        ]);

        $session = \App\Models\AttendanceSession::create([
            'tenant_id'         => $tenant->id,
            'academic_year_id'  => $academicYear->id,
            'study_group_id'    => $group->id,
            'tentor_id'         => $tentor->id,
            'date'              => now()->toDateString(),
            'subject_name'      => 'Matematika Dasar',
            'topic_description' => 'Logika Matematika',
        ]);

        \App\Models\Attendance::create([
            'tenant_id'             => $tenant->id,
            'attendance_session_id' => $session->id,
            'student_id'            => $student->id,
            'status'                => 'present',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('stats')
            ->has('recentSessions')
            ->has('recentStudentLogs')
        );
    }

    public function test_user_can_login_using_username(): void
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel Ceria',
            'slug'   => 'bimbel-ceria-' . Str::random(5),
            'phone'  => '081234567888',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Siswa Bintang',
            'username'  => '261001',
            'email'     => null,
            'password'  => Hash::make('4829'),
            'role'      => 'siswa',
            'status'    => 'active',
        ]);

        $response = $this->post('/login', [
            'email'    => '261001', // Flexible login field
            'password' => '4829',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
