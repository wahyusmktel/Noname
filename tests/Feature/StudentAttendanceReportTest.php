<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Tentor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private Tentor $tentor;
    private AcademicYear $academicYear;
    private StudyGroup $studyGroupSD;
    private StudyGroup $studyGroupSMA;
    private Student $studentSD;
    private Student $studentSMA;
    private Subject $subject;
    private AttendanceSession $sessionSD;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'         => 'Bimbel Prestasi',
            'slug'         => 'bimbel-prestasi',
            'brand_color'  => '#F97316',
            'package_type' => 'enterprise',
            'status'       => 'active',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Bimbel',
            'username'  => 'adminbimbel',
            'email'     => 'admin@bimbelprestasi.com',
            'password'  => 'password123',
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $this->academicYear = AcademicYear::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'is_active'  => true,
        ]);

        $this->tentor = Tentor::create([
            'tenant_id'      => $this->tenant->id,
            'name'           => 'Budi Raharjo',
            'title_suffix'   => 'M.Pd.',
            'specialization' => 'Matematika Terpadu',
            'status'         => 'active',
        ]);

        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Matematika Terpadu',
            'is_active' => true,
        ]);

        $this->studyGroupSD = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Kelompok SD Bintang',
            'education_level'  => 'SD',
            'is_active'        => true,
        ]);

        $this->studyGroupSMA = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Kelompok SMA Juara',
            'education_level'  => 'SMA',
            'is_active'        => true,
        ]);

        $this->studentSD = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroupSD->id,
            'name'             => 'Ananda Pratama',
            'nis'              => '261001',
            'status'           => 'active',
        ]);

        $this->studentSMA = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroupSMA->id,
            'name'             => 'Rian Hidayat',
            'nis'              => '261002',
            'status'           => 'active',
        ]);

        // Sesi Pertemuan
        $this->sessionSD = AttendanceSession::create([
            'tenant_id'         => $this->tenant->id,
            'tentor_id'         => $this->tentor->id,
            'study_group_id'    => $this->studyGroupSD->id,
            'academic_year_id'  => $this->academicYear->id,
            'date'              => now()->format('Y-m-d'),
            'subject_name'      => 'Matematika Terpadu',
            'topic_description' => 'Operasi Hitung Pecahan',
            'created_by_user_id'=> $this->adminUser->id,
        ]);

        Attendance::create([
            'tenant_id'             => $this->tenant->id,
            'attendance_session_id' => $this->sessionSD->id,
            'student_id'            => $this->studentSD->id,
            'status'                => 'present',
        ]);
    }

    public function test_guest_cannot_access_report(): void
    {
        $response = $this->get('/reports/student-attendance');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_student_attendance_report(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/reports/student-attendance');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Report/StudentAttendance')
                 ->has('kpi')
                 ->has('students_recap')
                 ->has('session_logs')
                 ->has('study_groups')
                 ->has('subjects')
                 ->where('kpi.total_sessions', 1)
                 ->where('kpi.total_present', 1)
                 ->where('kpi.total_absent', 0)
        );
    }

    public function test_filter_by_education_level(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/reports/student-attendance?education_level=SD');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('kpi.total_sessions', 1)
                 ->has('students_recap', 1)
                 ->where('students_recap.0.name', 'Ananda Pratama')
        );

        $responseSMA = $this->actingAs($this->adminUser)->get('/reports/student-attendance?education_level=SMA');

        $responseSMA->assertStatus(200);
        $responseSMA->assertInertia(fn ($page) =>
            $page->where('kpi.total_sessions', 0)
                 ->has('students_recap', 1)
                 ->where('students_recap.0.name', 'Rian Hidayat')
        );
    }

    public function test_filter_by_study_group(): void
    {
        $response = $this->actingAs($this->adminUser)->get("/reports/student-attendance?study_group_id={$this->studyGroupSD->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->has('students_recap', 1)
                 ->where('students_recap.0.name', 'Ananda Pratama')
        );
    }

    public function test_filter_by_subject(): void
    {
        $response = $this->actingAs($this->adminUser)->get("/reports/student-attendance?subject_name=Matematika Terpadu");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('kpi.total_sessions', 1)
        );

        $responseEmpty = $this->actingAs($this->adminUser)->get("/reports/student-attendance?subject_name=Fisika");
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertInertia(fn ($page) =>
            $page->where('kpi.total_sessions', 0)
        );
    }

    public function test_export_student_attendance_to_excel(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/reports/student-attendance/export?period=monthly');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
