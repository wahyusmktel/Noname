<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Tentor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $tutorUser1;
    protected User $tutorUser2;
    protected Tentor $tentor1;
    protected Tentor $tentor2;
    protected StudyGroup $studyGroup;

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
            'email'     => 'admin@bimbelnoname.com',
            'password'  => bcrypt('password123'),
            'role'      => 'admin_bimbel',
        ]);

        $this->tutorUser1 = User::create([
            'id'        => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'name'      => 'Guru Matematika',
            'email'     => 'guru1@bimbelnoname.com',
            'password'  => bcrypt('password123'),
            'role'      => 'tutor',
        ]);

        $this->tentor1 = Tentor::create([
            'id'        => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'user_id'   => $this->tutorUser1->id,
            'name'      => 'Guru Matematika',
            'email'     => 'guru1@bimbelnoname.com',
            'phone'     => '0811111111',
            'status'    => 'active',
        ]);

        $this->tutorUser2 = User::create([
            'id'        => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'name'      => 'Guru Fisika',
            'email'     => 'guru2@bimbelnoname.com',
            'password'  => bcrypt('password123'),
            'role'      => 'tutor',
        ]);

        $this->tentor2 = Tentor::create([
            'id'        => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'user_id'   => $this->tutorUser2->id,
            'name'      => 'Guru Fisika',
            'email'     => 'guru2@bimbelnoname.com',
            'phone'     => '0822222222',
            'status'    => 'active',
        ]);

        $academicYear = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name'      => '2025/2026',
            'is_active' => true,
        ]);

        $this->studyGroup = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $academicYear->id,
            'name'             => 'Kelas 12 IPA',
            'education_level'  => 'SMA',
            'is_active'        => true,
        ]);
    }

    public function test_tutor_cannot_access_tutor_attendance_report(): void
    {
        $response = $this->actingAs($this->tutorUser1)->get('/reports/tutor-attendance');

        // Harus dialihkan ke form absensi tutor dengan session error
        $response->assertRedirect('/tutor/attendance');
        $response->assertSessionHas('error');
    }

    public function test_tutor_cannot_access_admin_management_routes(): void
    {
        $response1 = $this->actingAs($this->tutorUser1)->get('/students');
        $response1->assertRedirect('/tutor/attendance');

        $response2 = $this->actingAs($this->tutorUser1)->get('/tentors');
        $response2->assertRedirect('/tutor/attendance');

        $response3 = $this->actingAs($this->tutorUser1)->get('/reports/student-attendance');
        $response3->assertRedirect('/tutor/attendance');
    }

    public function test_tutor_can_access_tutor_attendance_form(): void
    {
        $response = $this->actingAs($this->tutorUser1)->get('/tutor/attendance');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_tutor_attendance_report(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/tutor-attendance');
        $response->assertStatus(200);
    }

    public function test_tutor_notifications_only_show_their_own_sessions(): void
    {
        // Buat sesi untuk Guru 1
        AttendanceSession::create([
            'id'                => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'         => $this->tenant->id,
            'tentor_id'         => $this->tentor1->id,
            'study_group_id'    => $this->studyGroup->id,
            'subject_name'      => 'Matematika Wajib',
            'topic_description' => 'Membahas Matriks',
            'date'              => now(),
        ]);

        // Buat sesi untuk Guru 2
        AttendanceSession::create([
            'id'                => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id'         => $this->tenant->id,
            'tentor_id'         => $this->tentor2->id,
            'study_group_id'    => $this->studyGroup->id,
            'subject_name'      => 'Fisika Kuantum',
            'topic_description' => 'Membahas Gelombang',
            'date'              => now(),
        ]);

        // Request oleh Guru 1
        $response = $this->actingAs($this->tutorUser1)->get('/tutor/attendance');
        $response->assertStatus(200);

        $notifications = $response->viewData('page')['props']['recent_notifications'] ?? [];

        // Guru 1 hanya melihat sesi Matematika miliknya, tidak boleh melihat sesi Fisika Guru 2
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('Matematika Wajib', $notifications[0]['title']);
        $this->assertEquals('/tutor/attendance', $notifications[0]['url']);
    }
}
