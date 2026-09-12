<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Primary Tenant: Bimbel No Name
        $tenantNoName = Tenant::firstOrCreate(
            ['slug' => 'bimbel-no-name'],
            [
                'name'         => 'Bimbel No Name',
                'email'        => 'kontak@bimbelnoname.com',
                'phone'        => '081234567890',
                'city'         => 'Jakarta Selatan',
                'address'      => 'Jl. Pendidikan Utama No. 88, Gedung Bimbel No Name Lantai 1 & 2',
                'brand_color'  => '#F97316',
                'package_type' => 'pro',
                'status'       => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@bimbelnoname.com'],
            [
                'tenant_id' => $tenantNoName->id,
                'name'      => 'Kepala Bimbel No Name',
                'phone'     => '081234567890',
                'password'  => Hash::make('password123'),
                'role'      => 'admin_bimbel',
                'status'    => 'active',
            ]
        );

        // Seed Sample Tentors for Bimbel No Name
        $sampleTentors = [
            [
                'name'           => 'Aris Sudrajat',
                'title_prefix'   => 'Dr.',
                'title_suffix'   => 'M.Si.',
                'phone'          => '081234567801',
                'email'          => 'aris.sudrajat@bimbelnoname.com',
                'specialization' => 'Penalaran Matematika & TPS',
                'status'         => 'active',
            ],
            [
                'name'           => 'Siti Nurhaliza',
                'title_prefix'   => null,
                'title_suffix'   => 'S.Pd.',
                'phone'          => '081234567802',
                'email'          => 'siti.nurhaliza@bimbelnoname.com',
                'specialization' => 'Fisika Kuantum & IPA',
                'status'         => 'active',
            ],
            [
                'name'           => 'Amanda Putri',
                'title_prefix'   => null,
                'title_suffix'   => 'M.Ed.',
                'phone'          => '081234567803',
                'email'          => 'amanda.putri@bimbelnoname.com',
                'specialization' => 'Bahasa Inggris & TOEFL',
                'status'         => 'active',
            ],
            [
                'name'           => 'Reza Rahardian',
                'title_prefix'   => null,
                'title_suffix'   => 'S.Si.',
                'phone'          => '081234567804',
                'email'          => 'reza.rahardian@bimbelnoname.com',
                'specialization' => 'Biologi & Kimia Terapan',
                'status'         => 'active',
            ],
            [
                'name'           => 'Bambang Sudibyo',
                'title_prefix'   => 'Drs.',
                'title_suffix'   => 'M.Pd.',
                'phone'          => '081234567805',
                'email'          => 'bambang.sudibyo@bimbelnoname.com',
                'specialization' => 'Bahasa Indonesia & Literasi',
                'status'         => 'active',
            ],
        ];

        foreach ($sampleTentors as $tentorData) {
            \App\Models\Tentor::firstOrCreate(
                [
                    'tenant_id' => $tenantNoName->id,
                    'email'     => $tentorData['email'],
                ],
                $tentorData
            );
        }

        // Seed Sample Subjects (Mata Pelajaran) for Bimbel No Name
        $sampleSubjects = [
            'Matematika Wajib',
            'Matematika Peminatan',
            'Fisika Kuantum & Mekanika',
            'Kimia Organik & Anorganik',
            'Biologi Sel & Genetika',
            'Bahasa Indonesia & Literasi',
            'Bahasa Inggris & TOEFL',
            'TPS (Tes Potensi Skolastik)',
            'Penalaran Matematika SNBT',
            'Ekonomi & Akuntansi',
            'Sosiologi & Geografi',
        ];

        foreach ($sampleSubjects as $subjectName) {
            \App\Models\Subject::firstOrCreate(
                [
                    'tenant_id' => $tenantNoName->id,
                    'name'      => $subjectName,
                ],
                [
                    'is_active' => true,
                ]
            );
        }

        // Seed Academic Year for Bimbel No Name
        $academicYear = \App\Models\AcademicYear::firstOrCreate(
            [
                'tenant_id' => $tenantNoName->id,
                'name'      => '2024/2025',
            ],
            [
                'is_active'  => true,
                'start_date' => '2024-07-15',
                'end_date'   => '2025-06-20',
            ]
        );

        // Seed Study Groups (Kelompok Bimbel)
        $groupIPA = \App\Models\StudyGroup::firstOrCreate(
            [
                'tenant_id'        => $tenantNoName->id,
                'academic_year_id' => $academicYear->id,
                'name'             => 'Kelas 12 IPA 1',
            ],
            [
                'education_level'  => 'SMA',
                'is_active'        => true,
            ]
        );

        $groupIPS = \App\Models\StudyGroup::firstOrCreate(
            [
                'tenant_id'        => $tenantNoName->id,
                'academic_year_id' => $academicYear->id,
                'name'             => 'Kelas 12 IPS 1',
            ],
            [
                'education_level'  => 'SMA',
                'is_active'        => true,
            ]
        );

        $groupUTBK = \App\Models\StudyGroup::firstOrCreate(
            [
                'tenant_id'        => $tenantNoName->id,
                'academic_year_id' => $academicYear->id,
                'name'             => 'Intensif UTBK TPS',
            ],
            [
                'education_level'  => 'SMA',
                'is_active'        => true,
            ]
        );

        // Seed 50 Kelompok Bimbel dari Berkas PDF "Data Anggota Untuk Absensi.pdf"
        $this->call(StudyGroupPdfSeeder::class);

        // Seed Sample Students (Peserta Didik)
        $sampleStudents = [
            [
                'name'           => 'Ahmad Fauzi Rahman',
                'parent_phone'   => '081234567810',
                'student_phone'  => '085712345670',
                'study_group_id' => $groupIPA->id,
                'status'         => 'active',
            ],
            [
                'name'           => 'Siti Aisyah Wardani',
                'parent_phone'   => '081234567811',
                'student_phone'  => '085712345671',
                'study_group_id' => $groupIPA->id,
                'status'         => 'active',
            ],
            [
                'name'           => 'Budi Santoso Wibowo',
                'parent_phone'   => '081234567812',
                'student_phone'  => '085712345672',
                'study_group_id' => $groupIPS->id,
                'status'         => 'active',
            ],
            [
                'name'           => 'Nabila Zahra Syahrini',
                'parent_phone'   => '081234567813',
                'student_phone'  => '085712345673',
                'study_group_id' => $groupUTBK->id,
                'status'         => 'active',
            ],
            [
                'name'           => 'Dimas Anggara Putra',
                'parent_phone'   => '081234567814',
                'student_phone'  => '085712345674',
                'study_group_id' => null, // Belum masuk kelompok
                'status'         => 'active',
            ],
            [
                'name'           => 'Rizky Alamsyah',
                'parent_phone'   => '081234567815',
                'student_phone'  => '085712345675',
                'study_group_id' => $groupIPA->id,
                'status'         => 'inactive', // Siswa keluar dari bimbel
            ],
        ];

        foreach ($sampleStudents as $sData) {
            \App\Models\Student::firstOrCreate(
                [
                    'tenant_id'        => $tenantNoName->id,
                    'academic_year_id' => $academicYear->id,
                    'name'             => $sData['name'],
                ],
                $sData
            );
        }

        // 2. Secondary Demo Tenant (For Testing Multi-Tenant capability)
        $tenantBintang = Tenant::firstOrCreate(
            ['slug' => 'bintang-prestasi'],
            [
                'name'         => 'Bimbel Bintang Prestasi',
                'email'        => 'kontak@bintangprestasi.com',
                'phone'        => '081234567890',
                'city'         => 'Jakarta Selatan',
                'address'      => 'Jl. Margonda Raya No. 102, Jakarta Selatan',
                'brand_color'  => '#F97316',
                'package_type' => 'pro',
                'status'       => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@bintangprestasi.com'],
            [
                'tenant_id' => $tenantBintang->id,
                'name'      => 'Budi Pratama, S.Pd.',
                'phone'     => '081234567890',
                'password'  => Hash::make('password123'),
                'role'      => 'admin_bimbel',
                'status'    => 'active',
            ]
        );
    }
}
