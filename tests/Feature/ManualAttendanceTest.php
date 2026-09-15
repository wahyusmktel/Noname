<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Tentor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private Tentor $tentor;
    private AcademicYear $academicYear;
    private StudyGroup $studyGroup;
    private Student $student1;
    private Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name',
            'brand_color'  => '#3B82F6',
            'package_type' => 'pro',
            'status'       => 'active',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Bimbel',
            'username'  => 'adminbimbel',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => 'password123',
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $tutorUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Pak Guru Budi',
            'username'  => 'pakbudi',
            'email'     => 'budi@bimbelnoname.com',
            'password'  => 'password123',
            'role'      => 'tutor',
            'status'    => 'active',
        ]);

        $this->tentor = Tentor::create([
            'tenant_id'      => $this->tenant->id,
            'user_id'        => $tutorUser->id,
            'name'           => 'Budi Santoso',
            'title_suffix'   => 'M.Pd.',
            'specialization' => 'Fisika SMA',
            'email'          => 'budi@bimbelnoname.com',
            'status'         => 'active',
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
            'name'             => 'Kelas 12 IPA Intensif',
            'grade_level'      => '12',
            'program_type'     => 'SNBT',
            'capacity'         => 15,
            'status'           => 'active',
        ]);

        $userStudent1 = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Siti Rahma',
            'username'  => 'sitirahma',
            'email'     => 'siti@bimbelnoname.com',
            'password'  => 'password123',
            'role'      => 'siswa',
            'status'    => 'active',
        ]);

        $this->student1 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'user_id'          => $userStudent1->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroup->id,
            'nis'              => 'BNN-2026-001',
            'name'             => 'Siti Rahma',
            'gender'           => 'female',
            'parent_phone'     => '081234567890',
            'status'           => 'active',
        ]);

        $userStudent2 = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Bambang Tri',
            'username'  => 'bambangtri',
            'email'     => 'bambang@bimbelnoname.com',
            'password'  => 'password123',
            'role'      => 'siswa',
            'status'    => 'active',
        ]);

        $this->student2 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'user_id'          => $userStudent2->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroup->id,
            'nis'              => 'BNN-2026-002',
            'name'             => 'Bambang Tri',
            'gender'           => 'male',
            'parent_phone'     => '081234567891',
            'status'           => 'active',
        ]);
    }

    public function test_guest_cannot_access_manual_attendance(): void
    {
        $response = $this->get(route('attendance.manual'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_manual_attendance_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('attendance.manual'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Attendance/Manual')
            ->has('studyGroups')
            ->has('tentors')
            ->has('recentSessions')
        );
    }

    public function test_admin_can_store_manual_attendance_session(): void
    {
        $payload = [
            'tentor_id'         => $this->tentor->id,
            'study_group_id'    => $this->studyGroup->id,
            'date'              => '2026-09-10',
            'subject_name'      => 'Fisika Modern & Kuantum',
            'topic_description' => 'Teori Relativitas Khusus dan Efek Fotolistrik',
            'students'          => [
                [
                    'student_id' => $this->student1->id,
                    'status'     => 'present',
                    'notes'      => 'Hadir tepat waktu',
                ],
                [
                    'student_id' => $this->student2->id,
                    'status'     => 'absent',
                    'notes'      => 'Tidak hadir',
                ],
            ],
        ];

        $response = $this->from(route('attendance.manual'))
            ->actingAs($this->adminUser)
            ->post(route('attendance.manual.store'), $payload);

        $response->assertRedirect(route('attendance.manual'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_sessions', [
            'tenant_id'         => $this->tenant->id,
            'tentor_id'         => $this->tentor->id,
            'study_group_id'    => $this->studyGroup->id,
            'date'              => '2026-09-10 00:00:00',
            'subject_name'      => 'Fisika Modern & Kuantum',
            'topic_description' => 'Teori Relativitas Khusus dan Efek Fotolistrik',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student1->id,
            'status'     => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student2->id,
            'status'     => 'absent',
        ]);
    }

    public function test_admin_can_store_manual_attendance_with_photo(): void
    {
        $photo = UploadedFile::fake()->image('dokumentasi_susulan.jpg', 800, 600);

        $payload = [
            'tentor_id'           => $this->tentor->id,
            'study_group_id'      => $this->studyGroup->id,
            'date'                => '2026-09-11',
            'subject_name'        => 'Fisika Dasar',
            'topic_description'   => 'Hukum Newton II',
            'documentation_photo' => $photo,
            'students'            => [
                [
                    'student_id' => $this->student1->id,
                    'status'     => 'present',
                ],
            ],
        ];

        $response = $this->from(route('attendance.manual'))
            ->actingAs($this->adminUser)
            ->post(route('attendance.manual.store'), $payload);

        $response->assertRedirect(route('attendance.manual'));

        $session = AttendanceSession::where('subject_name', 'Fisika Dasar')->first();
        $this->assertNotNull($session);
        $this->assertNotNull($session->documentation_photo);
        Storage::disk('public')->assertExists($session->documentation_photo);
    }

    public function test_manual_attendance_validation(): void
    {
        $response = $this->from(route('attendance.manual'))
            ->actingAs($this->adminUser)
            ->post(route('attendance.manual.store'), []);

        $response->assertSessionHasErrors([
            'tentor_id',
            'study_group_id',
            'date',
            'subject_name',
            'topic_description',
            'students',
        ]);
    }

    public function test_admin_can_delete_attendance_session(): void
    {
        $session = AttendanceSession::create([
            'tenant_id'         => $this->tenant->id,
            'academic_year_id'  => $this->academicYear->id,
            'study_group_id'    => $this->studyGroup->id,
            'tentor_id'         => $this->tentor->id,
            'date'              => '2026-09-10',
            'subject_name'      => 'Kimia Organik',
            'topic_description' => 'Materi Reaksi Senyawa Hidrokarbon',
            'status'            => 'closed',
        ]);

        Attendance::create([
            'tenant_id'             => $this->tenant->id,
            'attendance_session_id' => $session->id,
            'student_id'            => $this->student1->id,
            'status'                => 'present',
            'check_in_method'       => 'manual',
        ]);

        $response = $this->from(route('attendance.manual'))
            ->actingAs($this->adminUser)
            ->delete(route('attendance.manual.destroy', $session));

        $response->assertRedirect(route('attendance.manual'));
        $response->assertSessionHas('success', 'Data sesi presensi berhasil dihapus.');

        $this->assertSoftDeleted('attendance_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_admin_can_delete_via_attendance_sessions_route(): void
    {
        $session = AttendanceSession::create([
            'tenant_id'         => $this->tenant->id,
            'academic_year_id'  => $this->academicYear->id,
            'study_group_id'    => $this->studyGroup->id,
            'tentor_id'         => $this->tentor->id,
            'date'              => '2026-09-11',
            'subject_name'      => 'Fisika Kuantum',
            'topic_description' => 'Materi Dualisme Gelombang Partikel',
            'status'            => 'closed',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('attendance-sessions.destroy', $session));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Data sesi presensi berhasil dihapus.');

        $this->assertSoftDeleted('attendance_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_user_avatar_url_falls_back_to_tentor_photo(): void
    {
        Storage::disk('public')->put('tentors/test_avatar.jpg', 'dummy image content');

        $tutorUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Guru Hadijah',
            'username'  => 'hadijah',
            'email'     => 'hadijah@bimbelnoname.com',
            'password'  => 'password123',
            'role'      => 'tutor',
            'avatar'    => null, // avatar on user is null
            'status'    => 'active',
        ]);

        $tentor = Tentor::create([
            'tenant_id'      => $this->tenant->id,
            'user_id'        => $tutorUser->id,
            'name'           => 'Hadijah S.Pd',
            'photo'          => 'tentors/test_avatar.jpg',
            'status'         => 'active',
        ]);

        $this->assertNotNull($tutorUser->avatar_url);
        $this->assertStringContainsString('tentors/test_avatar.jpg', $tutorUser->avatar_url);
    }
}
