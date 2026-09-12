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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $studentUser;
    private Student $student;
    private Tentor $tentor;
    private AcademicYear $academicYear;
    private StudyGroup $studyGroup;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'         => 'Bimbel Prestasi Teladan',
            'slug'         => 'bimbel-prestasi-teladan',
            'brand_color'  => '#4F46E5',
            'package_type' => 'enterprise',
            'status'       => 'active',
        ]);

        $this->academicYear = AcademicYear::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'is_active'  => true,
        ]);

        $this->studyGroup = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'SD Kelas 6 Hebat',
            'education_level'  => 'SD',
            'is_active'        => true,
        ]);

        $this->studentUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Ahmad Zaki Fauzan',
            'username'  => '261099',
            'email'     => 'zaki@test.com',
            'password'  => bcrypt('password123'),
            'role'      => 'siswa',
            'status'    => 'active',
        ]);

        $this->student = Student::create([
            'tenant_id'        => $this->tenant->id,
            'user_id'          => $this->studentUser->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroup->id,
            'name'             => 'Ahmad Zaki Fauzan',
            'username'         => '261099',
            'parent_name'      => 'Bapak Hendra',
            'parent_phone'     => '08123456789',
            'status'           => 'active',
        ]);

        $this->tentor = Tentor::create([
            'tenant_id'      => $this->tenant->id,
            'name'           => 'Kak Dimas',
            'title_suffix'   => 'S.Si.',
            'specialization' => 'IPA Sains',
            'status'         => 'active',
        ]);

        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'IPA Fisika Dasar',
            'is_active' => true,
        ]);
    }

    public function test_student_user_redirected_to_student_dashboard_from_root_dashboard(): void
    {
        $response = $this->actingAs($this->studentUser)->get('/dashboard');

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_student_dashboard_renders_with_parent_monitoring_data(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('photos/session1.jpg', 'photo1');
        \Illuminate\Support\Facades\Storage::disk('public')->put('photos/session2.jpg', 'photo2');

        // Create 2 attendance sessions
        $session1 = AttendanceSession::create([
            'tenant_id'           => $this->tenant->id,
            'tentor_id'           => $this->tentor->id,
            'study_group_id'      => $this->studyGroup->id,
            'academic_year_id'    => $this->academicYear->id,
            'date'                => '2026-09-10',
            'subject_name'        => 'IPA Fisika Dasar',
            'topic_description'   => 'Gaya dan Gerak Benda',
            'documentation_photo' => 'photos/session1.jpg',
            'created_by_user_id'  => $this->studentUser->id,
        ]);

        Attendance::create([
            'tenant_id'             => $this->tenant->id,
            'attendance_session_id' => $session1->id,
            'student_id'            => $this->student->id,
            'status'                => 'present',
            'notes'                 => 'Aktif bertanya di kelas',
        ]);

        $session2 = AttendanceSession::create([
            'tenant_id'           => $this->tenant->id,
            'tentor_id'           => $this->tentor->id,
            'study_group_id'      => $this->studyGroup->id,
            'academic_year_id'    => $this->academicYear->id,
            'date'                => '2026-09-11',
            'subject_name'        => 'IPA Fisika Dasar',
            'topic_description'   => 'Energi Kinetik dan Potensial',
            'documentation_photo' => 'photos/session2.jpg',
            'created_by_user_id'  => $this->studentUser->id,
        ]);

        Attendance::create([
            'tenant_id'             => $this->tenant->id,
            'attendance_session_id' => $session2->id,
            'student_id'            => $this->student->id,
            'status'                => 'present',
            'notes'                 => 'Mengerjakan tugas dengan baik',
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->has('student', fn (Assert $student) => $student
                ->where('name', 'Ahmad Zaki Fauzan')
                ->where('nis', '261099')
                ->where('study_group_name', 'SD Kelas 6 Hebat')
                ->where('education_level', 'SD')
                ->etc()
            )
            ->has('kpi', fn (Assert $kpi) => $kpi
                ->where('total_sessions', 2)
                ->where('present_count', 2)
                ->where('absent_count', 0)
                ->where('attendance_rate', 100)
                ->where('badge.title', 'Bintang Teladan Emas 🌟')
                ->where('consecutive_streak', 2)
                ->etc()
            )
            ->has('attendance_history', 2)
            ->has('gallery', 2)
            ->has('subjects_analysis', 1)
        );
    }

    public function test_student_dashboard_handles_unlinked_student_profile_gracefully(): void
    {
        $orphanUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Siswa Belum Terhubung',
            'username'  => 'orphan_user',
            'email'     => 'orphan@test.com',
            'password'  => bcrypt('password123'),
            'role'      => 'siswa',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($orphanUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->where('student', null)
            ->where('kpi', null)
            ->where('attendance_history', [])
        );
    }

    public function test_student_user_receives_student_attendance_notifications(): void
    {
        $session = AttendanceSession::create([
            'tenant_id'           => $this->tenant->id,
            'tentor_id'           => $this->tentor->id,
            'study_group_id'      => $this->studyGroup->id,
            'academic_year_id'    => $this->academicYear->id,
            'date'                => '2026-09-12',
            'subject_name'        => 'Matematika Dasar',
            'topic_description'   => 'Pecahan dan Desimal',
            'created_by_user_id'  => $this->studentUser->id,
        ]);

        Attendance::create([
            'tenant_id'             => $this->tenant->id,
            'attendance_session_id' => $session->id,
            'student_id'            => $this->student->id,
            'status'                => 'present',
            'notes'                 => 'Hadir tepat waktu',
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recent_notifications', 1)
            ->where('recent_notifications.0.url', '/student/dashboard')
            ->where('recent_notifications.0.title', '✅ Presensi Ananda: Hadir di Kelas')
        );
    }
}

