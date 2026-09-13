<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantLandingSetting extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'tenant_landing_settings';

    protected $fillable = [
        'tenant_id',
        'navbar_subtitle',
        'hero_slides',
        'quality_header',
        'quality_items',
        'parent_cta',
        'tentor_cta',
        'contact_section',
    ];

    protected $casts = [
        'hero_slides'     => 'array',
        'quality_header'  => 'array',
        'quality_items'   => 'array',
        'parent_cta'      => 'array',
        'tentor_cta'      => 'array',
        'contact_section' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Konfigurasi default standar landing page
     */
    public static function getDefaults(): array
    {
        return [
            'navbar_subtitle' => 'Standar Kualitas Bimbingan Belajar Modern',
            'hero_slides' => [
                [
                    'id'       => 1,
                    'badge'    => 'Standar Pengajaran Unggul',
                    'title'    => 'Pendidikan Berkualitas dengan Pendampingan Tutor Berdedikasi',
                    'subtitle' => 'Metode bimbingan belajar interaktif dan mendalam untuk membangun pemahaman konsep secara tuntas, bukan sekadar menghafal rumus.',
                    'tag'      => 'Tutor Berpengalaman',
                    'image'    => '/images/slide_quality_tutor.jpg',
                ],
                [
                    'id'       => 2,
                    'badge'    => 'Fasilitas & Suasana Nyaman',
                    'title'    => 'Lingkungan Belajar Kondusif untuk Fokus Maksimal',
                    'subtitle' => 'Kelas berukuran kecil didukung sarana belajar modern menciptakan suasana yang bersahabat dan memacu semangat belajar siswa.',
                    'tag'      => 'Kelas Kecil & Terarah',
                    'image'    => '/images/slide_modern_class.jpg',
                ],
                [
                    'id'       => 3,
                    'badge'    => 'Evaluasi & Capaian Prestasi',
                    'title'    => 'Bimbingan Terarah Menuju Prestasi Akademik Terbaik',
                    'subtitle' => 'Pemantauan perkembangan belajar yang terukur dan berkala membantu siswa meraih potensi terbaik dan percaya diri dalam menghadapi ujian.',
                    'tag'      => 'Capaian Terukur',
                    'image'    => '/images/slide_student_success.jpg',
                ],
            ],
            'quality_header' => [
                'badge'    => 'Standar & Dedikasi Kami',
                'title'    => 'Mengutamakan Mutu Pembelajaran & Karakter Siswa',
                'subtitle' => 'Kami percaya bahwa prestasi berkelanjutan bermula dari proses belajar yang terarah, suasana kelas yang suportif, dan pendampingan oleh pengajar yang mengayomi.',
            ],
            'quality_items' => [
                [
                    'id'     => 1,
                    'badge'  => 'Kurikulum Juara',
                    'title'  => 'Kurikulum & Modul Terstruktur',
                    'desc'   => 'Materi pembelajaran disusun secara tematik dan sistematis mengacu pada kurikulum terbaru. Disertai latihan pemahaman konsep bertahap dari dasar hingga penguasaan soal penalaran kompleks.',
                    'image'  => '/images/quality_curriculum.jpg',
                    'points' => [
                        'Modul belajar komprehensif & tersusun sistematis',
                        'Metode penalaran konsep tanpa hafalan buta',
                        'Rangkuman intisari materi di setiap bab bimbingan',
                    ],
                ],
                [
                    'id'     => 2,
                    'badge'  => 'Kelas Kondusif',
                    'title'  => 'Kelas Kecil & Pendampingan Personal',
                    'desc'   => 'Setiap ruang kelas dibatasi hanya untuk kelompok kecil (maksimal 12-15 siswa). Pengajar memiliki waktu dan ruang yang cukup untuk membimbing setiap anak sesuai kecepatan daya tangkapnya.',
                    'image'  => '/images/quality_mentoring.jpg',
                    'points' => [
                        'Bimbingan personal oleh tutor sabar dan komunikatif',
                        'Suasana kelas yang hangat, bersahabat, dan saling mendukung',
                        'Pendampingan intensif hingga siswa benar-benar paham',
                    ],
                ],
                [
                    'id'     => 3,
                    'badge'  => 'Evaluasi Berkala',
                    'title'  => 'Evaluasi Terarah & Pembinaan Karakter',
                    'desc'   => 'Kami memantau perkembangan akademik secara berkala dengan pendekatan yang menumbuhkan rasa percaya diri, kejujuran, dan ketekunan belajar siswa demi meraih prestasi terbaik.',
                    'image'  => '/images/quality_evaluation.jpg',
                    'points' => [
                        'Uji pemahaman dan evaluasi materi secara berkala',
                        'Apresiasi motivatif atas setiap peningkatan prestasi siswa',
                        'Pencatatan rekam jejak kemajuan belajar yang objektif',
                    ],
                ],
            ],
            'parent_cta' => [
                'badge'       => 'Khusus Orang Tua & Wali Murid',
                'title'       => 'Pantau Kehadiran & Kemajuan Belajar Ananda dengan Mudah',
                'desc'        => 'Bimbel menyediakan portal monitoring khusus bagi Ayah dan Bunda. Gunakan akun siswa (NIS / Email) yang telah diberikan untuk memantau aktivitas belajar ananda secara transparan langsung dari genggaman Anda.',
                'button_text' => 'Masuk ke Portal Orang Tua',
                'button_url'  => '/login',
                'image'       => '/images/cta_parent_portal.jpg',
                'points'      => [
                    'Melihat riwayat presensi kehadiran di setiap sesi kelas',
                    'Membaca rangkuman jurnal materi dan catatan perkembangan dari guru',
                    'Melihat foto dokumentasi kegiatan belajar anak di kelas',
                ],
            ],
            'tentor_cta' => [
                'badge'       => 'Portal Khusus Guru & Tentor Bimbel',
                'title'       => 'Kelola Presensi & Dokumentasi Mengajar dengan Cepat',
                'desc'        => 'Sistem presensi terpadu memudahkan Bapak dan Ibu guru mencatat kehadiran siswa di tiap sesi pertemuan, mengunggah foto dokumentasi kelas, dan mengisi jurnal materi bimbingan tanpa terbebani administrasi manual.',
                'button_text' => 'Masuk ke Portal Tentor',
                'button_url'  => '/login',
                'image'       => '/images/cta_tentor_portal.jpg',
                'points'      => [
                    'Buka sesi presensi dan catat kehadiran siswa per kelas secara instan',
                    'Unggah foto dokumentasi kelas langsung dari smartphone Anda',
                    'Isi jurnal materi ajar dan catatan perkembangan belajar siswa',
                ],
            ],
            'contact_section' => [
                'badge'            => 'Lembaga Bimbingan Belajar',
                'tagline_override' => 'Berkomitmen memberikan standar pendidikan terbaik, lingkungan belajar yang ramah anak, dan transparansi informasi akademik bagi seluruh keluarga siswa.',
            ],
        ];
    }
}
