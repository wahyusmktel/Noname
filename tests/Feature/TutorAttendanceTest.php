<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
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

class TutorAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $tutorUser;
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
            'name'         => 'Bimbel Teladan',
            'slug'         => 'bimbel-teladan',
            'brand_color'  => '#F97316',
            'package_type' => 'pro',
            'status'       => 'active',
        ]);

        $this->tutorUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Ahmad Fauzi, S.Pd.',
            'username'  => 'ahmadfauzi',
            'email'     => 'ahmad@bimbelteladan.com',
            'password'  => 'password123',
            'role'      => 'tutor',
            'status'    => 'active',
        ]);

        $this->tentor = Tentor::create([
            'tenant_id'      => $this->tenant->id,
            'user_id'        => $this->tutorUser->id,
            'name'           => 'Ahmad Fauzi',
            'title_suffix'   => 'S.Pd.',
            'specialization' => 'Matematika Saintek',
            'email'          => 'ahmad@bimbelteladan.com',
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
            'name'             => 'Kelompok Einstein 12 SMA',
            'education_level'  => 'SMA',
            'is_active'        => true,
        ]);

        $this->student1 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroup->id,
            'name'             => 'Budi Santoso',
            'status'           => 'active',
        ]);

        $this->student2 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $this->studyGroup->id,
            'name'             => 'Citra Lestari',
            'status'           => 'active',
        ]);
    }

    public function test_guest_cannot_access_tutor_attendance(): void
    {
        $response = $this->get('/tutor/attendance');
        $response->assertRedirect('/login');
    }

    public function test_tutor_accessing_dashboard_redirects_to_tutor_attendance(): void
    {
        $response = $this->actingAs($this->tutorUser)->get('/dashboard');
        $response->assertRedirect('/tutor/attendance');
    }

    public function test_tutor_can_view_attendance_create_page(): void
    {
        $response = $this->actingAs($this->tutorUser)->get('/tutor/attendance');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Tutor/Attendance/Create')
                 ->has('tentor')
                 ->has('study_groups')
                 ->where('tentor.name', 'Ahmad Fauzi, S.Pd.')
                 ->where('tentor.specialization', 'Matematika Saintek')
        );
    }

    public function test_tutor_can_store_attendance_successfully(): void
    {
        $file = UploadedFile::fake()->image('dokumentasi_kelas.jpg', 800, 600);

        $payload = [
            'date'                => '2026-09-12',
            'study_group_id'      => $this->studyGroup->id,
            'topic_description'   => 'Membahas bab 3 Logaritma dan Fungsi Eksponensial',
            'documentation_photo' => $file,
            'attendances'         => [
                [
                    'student_id' => $this->student1->id,
                    'status'     => 'present',
                ],
                [
                    'student_id' => $this->student2->id,
                    'status'     => 'absent',
                ],
            ],
        ];

        $response = $this->actingAs($this->tutorUser)->post('/tutor/attendance', $payload);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_sessions', [
            'tenant_id'         => $this->tenant->id,
            'tentor_id'         => $this->tentor->id,
            'study_group_id'    => $this->studyGroup->id,
            'topic_description' => 'Membahas bab 3 Logaritma dan Fungsi Eksponensial',
        ]);

        $session = AttendanceSession::first();
        $this->assertNotNull($session);
        $this->assertEquals('2026-09-12', $session->date->format('Y-m-d'));

        $this->assertDatabaseHas('attendances', [
            'tenant_id'  => $this->tenant->id,
            'student_id' => $this->student1->id,
            'status'     => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'tenant_id'  => $this->tenant->id,
            'student_id' => $this->student2->id,
            'status'     => 'absent',
        ]);
    }

    public function test_validation_prevents_duplicate_attendance_on_same_date_and_group(): void
    {
        // 1. Catat sesi pertama
        AttendanceSession::create([
            'tenant_id'         => $this->tenant->id,
            'tentor_id'         => $this->tentor->id,
            'study_group_id'    => $this->studyGroup->id,
            'date'              => '2026-09-12',
            'subject_name'      => 'Matematika Saintek',
            'topic_description' => 'Sesi Pertama',
        ]);

        // 2. Coba catat sesi kedua pada tanggal dan kelompok yang sama
        $payload = [
            'date'                => '2026-09-12',
            'study_group_id'      => $this->studyGroup->id,
            'topic_description'   => 'Sesi Kedua Duplikat',
            'documentation_photo' => UploadedFile::fake()->image('dokumentasi.jpg'),
            'attendances'         => [
                [
                    'student_id' => $this->student1->id,
                    'status'     => 'present',
                ],
            ],
        ];

        $response = $this->actingAs($this->tutorUser)->post('/tutor/attendance', $payload);

        // Harus gagal dengan error pencegahan duplikasi
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    public function test_validation_requires_documentation_photo(): void
    {
        $payload = [
            'date'                => '2026-09-13',
            'study_group_id'      => $this->studyGroup->id,
            'topic_description'   => 'Latihan Soal Try Out Tanpa Foto',
            'documentation_photo' => null,
            'attendances'         => [
                [
                    'student_id' => $this->student1->id,
                    'status'     => 'present',
                ],
            ],
        ];

        $response = $this->actingAs($this->tutorUser)->post('/tutor/attendance', $payload);
        $response->assertSessionHasErrors(['documentation_photo']);
        $this->assertDatabaseCount('attendance_sessions', 0);
    }

    public function test_tutor_without_specialization_requires_subject_selection(): void
    {
        // Set specialization tentor ke null
        $this->tentor->update(['specialization' => null]);

        $payload = [
            'date'                => '2026-09-14',
            'study_group_id'      => $this->studyGroup->id,
            'subject_name'        => null, // Tidak memilih mata pelajaran
            'topic_description'   => 'Membahas bab baru',
            'documentation_photo' => UploadedFile::fake()->image('dokumentasi.jpg'),
            'attendances'         => [
                [
                    'student_id' => $this->student1->id,
                    'status'     => 'present',
                ],
            ],
        ];

        $response = $this->actingAs($this->tutorUser)->post('/tutor/attendance', $payload);
        $response->assertSessionHasErrors(['subject_name']);
        $this->assertDatabaseCount('attendance_sessions', 0);
    }

    public function test_tutor_without_specialization_can_choose_subject(): void
    {
        // Set specialization tentor ke null
        $this->tentor->update(['specialization' => null]);

        $payload = [
            'date'                => '2026-09-14',
            'study_group_id'      => $this->studyGroup->id,
            'subject_name'        => 'Fisika Kuantum & IPA',
            'topic_description'   => 'Membahas mekanika gerak parabola',
            'documentation_photo' => UploadedFile::fake()->image('dokumentasi.jpg'),
            'attendances'         => [
                [
                    'student_id' => $this->student1->id,
                    'status'     => 'present',
                ],
            ],
        ];

        $response = $this->actingAs($this->tutorUser)->post('/tutor/attendance', $payload);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendance_sessions', 1);

        $this->assertDatabaseHas('attendance_sessions', [
            'subject_name' => 'Fisika Kuantum & IPA',
            'topic_description' => 'Membahas mekanika gerak parabola',
        ]);
    }
}

