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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private Tentor $tentor1;
    private Tentor $tentor2;
    private AcademicYear $academicYear;
    private StudyGroup $studyGroup;
    private Student $student;
    private AttendanceSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'         => 'Bimbel Hebat',
            'slug'         => 'bimbel-hebat',
            'brand_color'  => '#EA580C',
            'package_type' => 'enterprise',
            'status'       => 'active',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Bimbel Hebat',
            'username'  => 'adminhebat',
            'email'     => 'admin@hebat.com',
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

        $this->tentor1 = Tentor::create([
            'tenant_id'      => $this->tenant->id,
            'name'           => 'Rian Firmansyah',
            'title_suffix'   => 'S.Pd.',
            'specialization' => 'Fisika Terapan',
            'status'         => 'active',
            'phone'          => '081234567890',
        ]);

        $this->tentor2 = Tentor::create([
            'tenant_id'      => $this->tenant->id,
            'name'           => 'Dewi Anggraini',
            'title_suffix'   => 'M.Si.',
            'specialization' => 'Biologi Kedokteran',
            'status'         => 'active',
            'phone'          => '082345678901',
        ]);

        $this->studyGroup = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Kelompok Fisika SMA',
            'education_level'  => 'SMA',
            'is_active'        => true,
        ]);

        $this->student = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroup->id,
            'name'             => 'Citra Lestari',
            'nis'              => '26001',
            'status'           => 'active',
        ]);

        // Sesi Pertemuan Kelas yang dibuat oleh Tentor 1 pada bulan berjalan
        $this->session = AttendanceSession::create([
            'tenant_id'          => $this->tenant->id,
            'tentor_id'          => $this->tentor1->id,
            'study_group_id'     => $this->studyGroup->id,
            'academic_year_id'   => $this->academicYear->id,
            'date'               => Carbon::today()->format('Y-m-d'),
            'subject_name'       => 'Fisika Terapan',
            'topic_description'  => 'Hukum Newton & Dinamika Gerak Lurus',
            'created_by_user_id' => $this->adminUser->id,
        ]);

        Attendance::create([
            'tenant_id'             => $this->tenant->id,
            'attendance_session_id' => $this->session->id,
            'student_id'            => $this->student->id,
            'status'                => 'present',
        ]);
    }

    public function test_admin_can_access_tutor_attendance_report_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('reports.tutor-attendance'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Report/TutorAttendance')
                ->has('filters')
                ->has('kpi')
                ->has('tentors_recap')
                ->has('session_logs')
        );
    }

    public function test_teacher_who_records_student_attendance_is_automatically_counted_as_present(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('reports.tutor-attendance'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $tentorsRecap = $page->toArray()['props']['tentors_recap'];

            // Tentor 1 mengajar 1 sesi -> otomatis terhitung total_sessions = 1
            $recap1 = collect($tentorsRecap)->firstWhere('id', $this->tentor1->id);
            $this->assertNotNull($recap1);
            $this->assertEquals(1, $recap1['total_sessions']);
            $this->assertEquals(1, $recap1['total_students_taught']);
            $this->assertEquals(100, $recap1['avg_class_attendance_rate']);
            $this->assertEquals('Cukup Aktif', $recap1['status_label']);
            $this->assertCount(1, $recap1['session_history']);

            // Tentor 2 belum melakukan absensi kelas -> total_sessions = 0
            $recap2 = collect($tentorsRecap)->firstWhere('id', $this->tentor2->id);
            $this->assertNotNull($recap2);
            $this->assertEquals(0, $recap2['total_sessions']);
            $this->assertEquals('Belum Ada Sesi', $recap2['status_label']);
        });
    }

    public function test_tutor_attendance_report_can_be_filtered_by_search_name(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('reports.tutor-attendance', [
            'search' => 'Rian',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $tentorsRecap = $page->toArray()['props']['tentors_recap'];
            $this->assertCount(1, $tentorsRecap);
            $this->assertEquals($this->tentor1->id, $tentorsRecap[0]['id']);
        });
    }

    public function test_tutor_attendance_report_can_be_filtered_by_period(): void
    {
        // Filter harian untuk hari ini
        $responseDaily = $this->actingAs($this->adminUser)->get(route('reports.tutor-attendance', [
            'period' => 'daily',
            'date'   => Carbon::today()->format('Y-m-d'),
        ]));

        $responseDaily->assertStatus(200);
        $responseDaily->assertInertia(fn ($page) =>
            $page->where('kpi.total_sessions', 1)
        );

        // Filter harian untuk kemarin (seharusnya tidak ada sesi)
        $responsePast = $this->actingAs($this->adminUser)->get(route('reports.tutor-attendance', [
            'period' => 'daily',
            'date'   => Carbon::yesterday()->format('Y-m-d'),
        ]));

        $responsePast->assertStatus(200);
        $responsePast->assertInertia(fn ($page) =>
            $page->where('kpi.total_sessions', 0)
        );
    }

    public function test_tutor_attendance_report_can_be_exported_to_excel(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('reports.tutor-attendance.export', [
            'period' => 'monthly',
        ]));

        $response->assertStatus(200);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('Rekap_Kehadiran_Guru_monthly_', $response->headers->get('Content-Disposition'));
    }

    public function test_multi_tenant_isolation_in_tutor_attendance_report(): void
    {
        $tenantLain = Tenant::create([
            'name'         => 'Bimbel Lain',
            'slug'         => 'bimbel-lain',
            'brand_color'  => '#3B82F6',
            'package_type' => 'basic',
            'status'       => 'active',
        ]);

        $adminLain = User::create([
            'tenant_id' => $tenantLain->id,
            'name'      => 'Admin Bimbel Lain',
            'username'  => 'adminlain',
            'email'     => 'admin@lain.com',
            'password'  => 'password123',
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        // User dari tenant lain tidak boleh melihat data sesi atau guru tenant pertama
        $response = $this->actingAs($adminLain)->get(route('reports.tutor-attendance'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('kpi.total_active_tentors', 0)
                ->where('kpi.total_sessions', 0)
        );
    }

    public function test_recent_attendance_activity_shared_in_notifications(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $notifs = $page->toArray()['props']['recent_notifications'];
            $this->assertNotEmpty($notifs);
            $this->assertStringContainsString('Presensi Baru: Rian Firmansyah', $notifs[0]['title']);
            $this->assertStringContainsString('Kelompok Fisika SMA', $notifs[0]['desc']);
        });
    }
}

