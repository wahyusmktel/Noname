<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentAndGroupTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private AcademicYear $academicYear;

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

        $this->academicYear = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name'      => '2024/2025',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_students_or_groups(): void
    {
        $this->get('/students')->assertRedirect('/login');
        $this->get('/study-groups')->assertRedirect('/login');
        $this->get('/academic-years')->assertRedirect('/login');
    }

    public function test_academic_year_crud_and_set_active(): void
    {
        // 1. Buat tahun baru
        $response = $this->actingAs($this->user)->post('/academic-years', [
            'name'      => '2025/2026',
            'is_active' => false,
        ]);
        $response->assertRedirect();

        $newYear = AcademicYear::where('name', '2025/2026')->first();
        $this->assertNotNull($newYear);

        // 2. Set tahun baru menjadi aktif
        $setActiveResponse = $this->actingAs($this->user)->post("/academic-years/{$newYear->id}/set-active");
        $setActiveResponse->assertRedirect();

        $this->assertTrue($newYear->fresh()->is_active);
        $this->assertFalse($this->academicYear->fresh()->is_active);
    }

    public function test_study_group_crud_and_mapping(): void
    {
        // 1. Buat kelompok bimbel
        $response = $this->actingAs($this->user)->post('/study-groups', [
            'name'             => 'Kelas 12 IPA 1',
            'education_level'  => 'SMA',
            'academic_year_id' => $this->academicYear->id,
            'is_active'        => true,
        ]);
        $response->assertRedirect();

        $group = StudyGroup::where('name', 'Kelas 12 IPA 1')->first();
        $this->assertNotNull($group);
        $this->assertEquals('SMA', $group->education_level);

        // 2. Buat 2 siswa
        $student1 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Siswa A',
            'status'           => 'active',
        ]);
        $student2 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Siswa B',
            'status'           => 'active',
        ]);

        // 3. Mapping siswa ke kelompok
        $mapResponse = $this->actingAs($this->user)->post("/study-groups/{$group->id}/map-students", [
            'student_ids' => [$student1->id, $student2->id],
        ]);
        $mapResponse->assertRedirect();

        $this->assertEquals($group->id, $student1->fresh()->study_group_id);
        $this->assertEquals($group->id, $student2->fresh()->study_group_id);
    }

    public function test_student_crud_and_status_toggle(): void
    {
        // 1. Tambah siswa
        $response = $this->actingAs($this->user)->post('/students', [
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Ahmad Santoso',
            'parent_phone'     => '081234567890',
            'student_phone'    => '085712345678',
            'status'           => 'active',
        ]);
        $response->assertRedirect();

        $student = Student::where('name', 'Ahmad Santoso')->first();
        $this->assertNotNull($student);
        $this->assertEquals('active', $student->status);

        // 2. Nonaktifkan siswa (keluar dari bimbel)
        $toggleResponse = $this->actingAs($this->user)->post("/students/{$student->id}/toggle-status");
        $toggleResponse->assertRedirect();
        $this->assertEquals('inactive', $student->fresh()->status);

        // 3. Aktifkan kembali
        $toggleResponse2 = $this->actingAs($this->user)->post("/students/{$student->id}/toggle-status");
        $toggleResponse2->assertRedirect();
        $this->assertEquals('active', $student->fresh()->status);

        // 4. Soft delete siswa
        $deleteResponse = $this->actingAs($this->user)->delete("/students/{$student->id}");
        $deleteResponse->assertRedirect();
        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_excel_template_download(): void
    {
        $response = $this->actingAs($this->user)->get('/students/export-template');
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=Template_Import_Peserta_Didik.xlsx');
    }

    public function test_excel_import_with_auto_group_mapping(): void
    {
        // Buat file excel sementara
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Nama Siswa *');
        $sheet->setCellValue('B1', 'No HP Orang Tua');
        $sheet->setCellValue('C1', 'No HP Siswa');
        $sheet->setCellValue('D1', 'Nama Kelompok Bimbel');
        $sheet->setCellValue('E1', 'Status');

        $sheet->setCellValue('A2', 'Rian Hidayat');
        $sheet->setCellValue('B2', '081299998888');
        $sheet->setCellValue('C2', '085799998888');
        $sheet->setCellValue('D2', 'Kelas Intensif 12');
        $sheet->setCellValue('E2', 'Aktif');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'test_siswa.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->user)->post('/students/import-excel', [
            'academic_year_id' => $this->academicYear->id,
            'file'             => $uploadedFile,
        ]);

        $response->assertRedirect();

        // Verifikasi kelompok otomatis dibuat
        $group = StudyGroup::where('name', 'Kelas Intensif 12')
            ->where('academic_year_id', $this->academicYear->id)
            ->first();
        $this->assertNotNull($group);

        // Verifikasi siswa terbuat dan otomatis masuk kelompok
        $student = Student::where('name', 'Rian Hidayat')
            ->where('academic_year_id', $this->academicYear->id)
            ->first();
        $this->assertNotNull($student);
        $this->assertEquals($group->id, $student->study_group_id);
        $this->assertEquals('081299998888', $student->parent_phone);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_copy_students_and_groups_between_academic_years(): void
    {
        // Tahun Pelajaran Baru
        $nextYear = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name'      => '2025/2026',
            'is_active' => false,
        ]);

        // Kelompok dan siswa di tahun sumber
        $group = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Kelompok Alpha',
            'education_level'  => 'SMP',
            'is_active'        => true,
        ]);

        $student = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $group->id,
            'name'             => 'Gita Gutawa',
            'status'           => 'active',
        ]);

        // 1. Salin Siswa ke tahun baru
        $copyStudentsResponse = $this->actingAs($this->user)->post('/students/copy-from-previous', [
            'source_year_id' => $this->academicYear->id,
            'target_year_id' => $nextYear->id,
        ]);
        $copyStudentsResponse->assertRedirect();

        $copiedStudent = Student::where('academic_year_id', $nextYear->id)
            ->where('name', 'Gita Gutawa')
            ->first();
        $this->assertNotNull($copiedStudent);

        // 2. Salin Kelompok & Anggota ke tahun baru
        $copyGroupsResponse = $this->actingAs($this->user)->post('/study-groups/copy-from-previous', [
            'source_year_id' => $this->academicYear->id,
            'target_year_id' => $nextYear->id,
        ]);
        $copyGroupsResponse->assertRedirect();

        $copiedGroup = StudyGroup::where('academic_year_id', $nextYear->id)
            ->where('name', 'Kelompok Alpha')
            ->first();
        $this->assertNotNull($copiedGroup);

        // Verifikasi siswa di tahun baru sekarang sudah termaping ke kelompok baru
        $this->assertEquals($copiedGroup->id, $copiedStudent->fresh()->study_group_id);
    }

    public function test_study_group_education_levels_and_excel_autodetection(): void
    {
        // 1. Validasi input jenjang di kelompok bimbel
        $response = $this->actingAs($this->user)->post('/study-groups', [
            'name'             => 'Kelompok 6B',
            'education_level'  => 'SD',
            'academic_year_id' => $this->academicYear->id,
            'is_active'        => true,
        ]);
        $response->assertRedirect();

        $groupSD = StudyGroup::where('name', 'Kelompok 6B')->first();
        $this->assertNotNull($groupSD);
        $this->assertEquals('SD', $groupSD->education_level);

        // 2. Buat berkas excel sederhana dengan kelompok berbeda untuk menguji auto-detection
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Nama Siswa *');
        $sheet->setCellValue('B1', 'No HP Orang Tua');
        $sheet->setCellValue('C1', 'No HP Siswa');
        $sheet->setCellValue('D1', 'Nama Kelompok Bimbel');
        $sheet->setCellValue('E1', 'Status (Aktif / Nonaktif)');
        $sheet->setCellValue('F1', 'Jenjang (SD / SMP / SMA)');

        // Baris data dengan nama kelompok bervariasi
        $sheet->setCellValue('A2', 'Siswa SD');
        $sheet->setCellValue('D2', '5'); // Angka 5 -> SD
        $sheet->setCellValue('E2', 'Aktif');

        $sheet->setCellValue('A3', 'Siswa SMP');
        $sheet->setCellValue('D3', '8D'); // Angka 8 -> SMP
        $sheet->setCellValue('E3', 'Aktif');

        $sheet->setCellValue('A4', 'Siswa SMA');
        $sheet->setCellValue('D4', 'CYNDAQUIL'); // Pokemon -> SMA
        $sheet->setCellValue('E4', 'Aktif');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempPath,
            'test_import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $importResponse = $this->actingAs($this->user)->post('/students/import-excel', [
            'academic_year_id' => $this->academicYear->id,
            'file'             => $uploadedFile,
        ]);
        $importResponse->assertRedirect();

        $autoGroupSD = StudyGroup::where('name', '5')->first();
        $autoGroupSMP = StudyGroup::where('name', '8D')->first();
        $autoGroupSMA = StudyGroup::where('name', 'CYNDAQUIL')->first();

        $this->assertNotNull($autoGroupSD);
        $this->assertEquals('SD', $autoGroupSD->education_level);

        $this->assertNotNull($autoGroupSMP);
        $this->assertEquals('SMP', $autoGroupSMP->education_level);

        $this->assertNotNull($autoGroupSMA);
        $this->assertEquals('SMA', $autoGroupSMA->education_level);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    public function test_generate_student_accounts_format_and_role(): void
    {
        $group = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Kelompok Alpha',
            'education_level'  => 'SMP',
            'is_active'        => true,
        ]);

        $student1 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $group->id,
            'name'             => 'Ahmad Zaki',
            'status'           => 'active',
        ]);

        $student2 = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $group->id,
            'name'             => 'Budi Santoso',
            'status'           => 'active',
        ]);

        $response = $this->actingAs($this->user)->post('/students/generate-accounts', [
            'academic_year_id' => $this->academicYear->id,
        ]);
        $response->assertRedirect();

        $student1->refresh();
        $student2->refresh();

        $this->assertNotNull($student1->username);
        $this->assertNotNull($student1->plain_password);
        $this->assertNotNull($student2->username);
        $this->assertNotNull($student2->plain_password);

        // Check format: 5+ digits, starts with 2 (e.g. 241001 or 261001), 4 digit plain password
        $this->assertMatchesRegularExpression('/^\d{5,}$/', $student1->username);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $student1->plain_password);

        // Check associated User model
        $user1 = User::find($student1->user_id);
        $this->assertNotNull($user1);
        $this->assertEquals('siswa', $user1->role);
        $this->assertTrue(Hash::check($student1->plain_password, $user1->password));
    }

    public function test_reset_student_password(): void
    {
        $student = Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Cahya Kamila',
            'status'           => 'active',
        ]);

        // Generate account first
        $this->actingAs($this->user)->post('/students/generate-accounts', [
            'academic_year_id' => $this->academicYear->id,
        ]);
        $student->refresh();
        $oldPassword = $student->plain_password;

        // Reset password
        $response = $this->actingAs($this->user)->post("/students/{$student->id}/reset-password");
        $response->assertRedirect();

        $student->refresh();
        $this->assertNotNull($student->plain_password);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $student->plain_password);

        $user = User::find($student->user_id);
        $this->assertTrue(Hash::check($student->plain_password, $user->password));
    }

    public function test_export_student_accounts_excel_multi_sheet(): void
    {
        $group = StudyGroup::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'name'             => 'Kelompok Beta',
            'education_level'  => 'SMA',
            'is_active'        => true,
        ]);

        Student::create([
            'tenant_id'        => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'study_group_id'   => $group->id,
            'name'             => 'Doni Pratama',
            'username'         => '261001',
            'plain_password'   => '1234',
            'status'           => 'active',
        ]);

        $response = $this->actingAs($this->user)->get('/students/export-accounts?academic_year_id=' . $this->academicYear->id);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
