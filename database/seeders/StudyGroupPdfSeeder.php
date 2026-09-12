<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\StudyGroup;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class StudyGroupPdfSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mendaftarkan 50 Kelompok Bimbel berdasarkan berkas "Data Anggota Untuk Absensi.pdf"
     * dengan pemetaan jenjang:
     * - SD : Kelompok angka 5 sampai 6
     * - SMP: Kelompok angka 7 sampai 9
     * - SMA: Kelompok dengan penamaan karakter/pokemon (CYNDAQUIL, MUDKIP, dsb.)
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'bimbel-no-name')->first() ?? Tenant::first();
        if (!$tenant) {
            return;
        }

        $academicYear = AcademicYear::where('tenant_id', $tenant->id)->where('is_active', true)->first()
            ?? AcademicYear::where('tenant_id', $tenant->id)->first();

        if (!$academicYear) {
            return;
        }

        $groupsData = [
            // --- JENJANG SD (Angka 5 sampai 6) ---
            ['name' => '5',  'education_level' => 'SD'],
            ['name' => '6A', 'education_level' => 'SD'],
            ['name' => '6B', 'education_level' => 'SD'],
            ['name' => '6C', 'education_level' => 'SD'],

            // --- JENJANG SMP (Angka 7 sampai 9) ---
            ['name' => '7U',   'education_level' => 'SMP'],
            ['name' => '7A',   'education_level' => 'SMP'],
            ['name' => '7B',   'education_level' => 'SMP'],
            ['name' => '7C',   'education_level' => 'SMP'],
            ['name' => '7M',   'education_level' => 'SMP'],
            ['name' => '8U',   'education_level' => 'SMP'],
            ['name' => '8A',   'education_level' => 'SMP'],
            ['name' => '8B',   'education_level' => 'SMP'],
            ['name' => '8C',   'education_level' => 'SMP'],
            ['name' => '8D',   'education_level' => 'SMP'],
            ['name' => '8M',   'education_level' => 'SMP'],
            ['name' => '9 U1', 'education_level' => 'SMP'],
            ['name' => '9 U2', 'education_level' => 'SMP'],
            ['name' => '9A',   'education_level' => 'SMP'],
            ['name' => '9B',   'education_level' => 'SMP'],
            ['name' => '9C',   'education_level' => 'SMP'],
            ['name' => '9D',   'education_level' => 'SMP'],
            ['name' => '9E',   'education_level' => 'SMP'],
            ['name' => '9F',   'education_level' => 'SMP'],
            ['name' => '9G',   'education_level' => 'SMP'],
            ['name' => '9H',   'education_level' => 'SMP'],
            ['name' => '9M',   'education_level' => 'SMP'],

            // --- JENJANG SMA (Karakter / Pokemon) ---
            ['name' => 'CYNDAQUIL',  'education_level' => 'SMA'],
            ['name' => 'MUDKIP',     'education_level' => 'SMA'],
            ['name' => 'SKITTY',     'education_level' => 'SMA'],
            ['name' => 'TOTODILE',   'education_level' => 'SMA'],
            ['name' => 'ZIGZAGOON',  'education_level' => 'SMA'],
            ['name' => 'BAYLEEF',    'education_level' => 'SMA'],
            ['name' => 'COMBUSKEN',  'education_level' => 'SMA'],
            ['name' => 'GROVYLE',    'education_level' => 'SMA'],
            ['name' => 'GROWLITHE',  'education_level' => 'SMA'],
            ['name' => 'LAIRON',     'education_level' => 'SMA'],
            ['name' => 'NUZLEAF',    'education_level' => 'SMA'],
            ['name' => 'PRINPLUP',   'education_level' => 'SMA'],
            ['name' => 'RIOLU',      'education_level' => 'SMA'],
            ['name' => 'VULPIX',     'education_level' => 'SMA'],
            ['name' => 'WARTORTLE',  'education_level' => 'SMA'],
            ['name' => 'BLASTOISE',  'education_level' => 'SMA'],
            ['name' => 'CLEFABLE',   'education_level' => 'SMA'],
            ['name' => 'FERALIGATR', 'education_level' => 'SMA'],
            ['name' => 'JOLTEON',    'education_level' => 'SMA'],
            ['name' => 'VAPOREON',   'education_level' => 'SMA'],
            ['name' => 'MEGANIUM',   'education_level' => 'SMA'],
            ['name' => 'NINETALES',  'education_level' => 'SMA'],
            ['name' => 'Venusaur',   'education_level' => 'SMA'],
            ['name' => 'GLACEON',    'education_level' => 'SMA'],
        ];

        foreach ($groupsData as $item) {
            StudyGroup::updateOrCreate(
                [
                    'tenant_id'        => $tenant->id,
                    'academic_year_id' => $academicYear->id,
                    'name'             => $item['name'],
                ],
                [
                    'education_level'  => $item['education_level'],
                    'is_active'        => true,
                ]
            );
        }
    }
}
