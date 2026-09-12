<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpCenterController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman Pusat Bantuan & Panduan untuk Setiap Role
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->loadMissing('tenant');

        // Panduan Lengkap berdasarkan Peran (Role)
        $guidesByRole = [
            'admin_bimbel' => [
                'role_label' => 'Administrator Bimbel',
                'description' => 'Panduan lengkap pengelolaan master data lembaga, akun pengajar, siswa, dan rekapitulasi kehadiran.',
                'sections' => [
                    [
                        'id' => 'admin-master',
                        'title' => '1. Pengelolaan Data Lembaga & Tahun Ajaran',
                        'steps' => [
                            'Atur profil lembaga melalui menu Lembaga Bimbel > Profil Lembaga untuk mengubah nama, logo, nomor kontak, dan warna tema.',
                            'Kelola Tahun Pelajaran di menu Lembaga Bimbel > Tahun Pelajaran. Pastikan satu tahun ajaran aktif telah dipilih sebagai rujukan presensi.',
                            'Buat Mata Pelajaran di menu Manajemen Data > Mata Pelajaran dan Kelompok Bimbel (SD, SMP, SMA, Alumni).',
                        ],
                    ],
                    [
                        'id' => 'admin-tentor',
                        'title' => '2. Manajemen Tentor & Akun Guru',
                        'steps' => [
                            'Tambahkan guru baru di Manajemen Data > Tentor.',
                            'Gunakan tombol "Generate Akun Tentor" untuk membuat username dan password login otomatis bagi guru.',
                            'Unduh daftar akun melalui fitur "Ekspor Excel Kredensial" untuk dibagikan kepada masing-masing guru.',
                        ],
                    ],
                    [
                        'id' => 'admin-students',
                        'title' => '3. Data Siswa & Generate Akun Massal',
                        'steps' => [
                            'Unduh template Excel resmi di menu Manajemen Data > Peserta Didik > "Download Template".',
                            'Unggah data siswa secara massal menggunakan fitur "Import Excel".',
                            'Petakan siswa ke kelompok bimbel yang sesuai (Mapping Kelas).',
                            'Klik "Generate Akun Siswa" lalu unduh daftar kredensial untuk dibagikan kepada orang tua/wali murid.',
                        ],
                    ],
                    [
                        'id' => 'admin-reports',
                        'title' => '4. Rekapitulasi & Ekspor Laporan Excel',
                        'steps' => [
                            'Pantau rekap presensi di menu Laporan & Rekapitulasi > Kehadiran Peserta Didik.',
                            'Gunakan filter mata pelajaran, kelompok bimbel, dan jenjang pendidikan.',
                            'Klik tombol "Export Laporan Excel" di pojok kanan untuk mengunduh rekap format resmi (Harian, Mingguan, Bulanan, atau Seluruh Waktu).',
                            'Pantau rekapitulasi keaktifan guru di menu Laporan & Rekapitulasi > Kehadiran Guru.',
                        ],
                    ],
                ],
            ],

            'tutor' => [
                'role_label' => 'Guru / Tentor Bimbel',
                'description' => 'Panduan melakukan presensi kelas, penandaan status kehadiran siswa, dan pengunggahan foto dokumentasi tatap muka.',
                'sections' => [
                    [
                        'id' => 'tutor-attendance-open',
                        'title' => '1. Membuka Formulir Presensi Pertemuan',
                        'steps' => [
                            'Login menggunakan username dan password tentor yang diberikan oleh admin bimbel.',
                            'Sistem akan otomatis mengarahkan Anda ke halaman Absensi Siswa (/tutor/attendance).',
                            'Pilih Jenjang Pendidikan dan Kelompok Bimbel yang Anda ajar hari ini.',
                            'Daftar nama peserta didik aktif di kelompok tersebut akan otomatis ditampilkan di tabel.',
                        ],
                    ],
                    [
                        'id' => 'tutor-attendance-mark',
                        'title' => '2. Menandai Kehadiran Siswa',
                        'steps' => [
                            'Klik tombol "Hadir" (hijau) atau "Tidak Hadir" (merah) untuk masing-masing siswa satu per satu.',
                            'Anda juga dapat menggunakan tombol bantuan "Tandai Semua Hadir" di atas tabel jika seluruh siswa hadir.',
                            'Tambahkan catatan opsional jika siswa terlambat atau memiliki catatan belajar khusus.',
                        ],
                    ],
                    [
                        'id' => 'tutor-attendance-photo',
                        'title' => '3. Mengunggah Foto Dokumentasi Kelas (Wajib)',
                        'steps' => [
                            'Sesuai standar operasional bimbel, guru WAJIB mengunggah minimal 1 foto dokumentasi kegiatan belajar di kelas.',
                            'Tekan kotak kamera foto untuk mengambil foto langsung dari kamera smartphone atau memilih dari galeri.',
                            'Sistem dilengkapi fitur Auto-Compress otomatis sehingga foto resolusi tinggi langsung diperkecil tanpa menghabiskan kuota.',
                        ],
                    ],
                    [
                        'id' => 'tutor-attendance-journal',
                        'title' => '4. Mengisi Jurnal Materi & Menyimpan Presensi',
                        'steps' => [
                            'Tuliskan materi pembelajaran yang diajarkan pada kolom Jurnal Kelas (minimal 5 karakter).',
                            'Pastikan tidak ada siswa yang belum ditentukan status kehadirannya.',
                            'Klik tombol "Simpan Data Absensi Siswa" dan konfirmasi pada dialog pop-up yang muncul.',
                            'Kehadiran Anda sebagai guru otomatis tercatat dalam sistem kehadiran lembaga.',
                        ],
                    ],
                ],
            ],

            'siswa' => [
                'role_label' => 'Wali Murid / Siswa (Portal Monitoring Ananda)',
                'description' => 'Panduan bagi orang tua/wali murid dalam memantau kehadiran anak, catatan evaluasi tentor, dan foto aktivitas di kelas.',
                'sections' => [
                    [
                        'id' => 'parent-login',
                        'title' => '1. Masuk ke Portal Monitoring Ananda',
                        'steps' => [
                            'Gunakan username (NIS) dan password ananda yang diberikan oleh pengelola bimbel untuk login.',
                            'Setelah berhasil masuk, Anda akan langsung disambut pada Ruang Pantau Belajar Ananda.',
                            'Periksa nama ananda, kelompok bimbel, jenjang, dan tahun ajaran aktif pada kartu identitas atas.',
                        ],
                    ],
                    [
                        'id' => 'parent-kpi',
                        'title' => '2. Memahami Indikator Disiplin & Lencana Apresiasi',
                        'steps' => [
                            'Lihat 4 kartu ringkasan di bagian atas: Total Sesi Kelas, Hadir di Kelas, Izin/Tidak Hadir, dan Persentase Kehadiran.',
                            'Lencana Apresiasi (Bintang Teladan Emas, Bintang Rajin Perak, Bintang Semangat Perunggu) diberikan otomatis sesuai tingkat kedisiplinan ananda.',
                            'Pantau indikator "Streak Kehadiran" yang menunjukkan jumlah pertemuan berturut-turut yang dihadiri ananda tanpa absen.',
                        ],
                    ],
                    [
                        'id' => 'parent-navigation',
                        'title' => '3. Navigasi 3 Menu Utama (Ramah Smartphone)',
                        'steps' => [
                            'Gunakan 3 tombol menu yang pas di layar HP tanpa perlu digeser: [Analisis], [Jurnal], dan [Galeri Foto].',
                            'Tab Analisis: Memantau persentase kehadiran ananda per mata pelajaran dan membaca tips pendampingan kasih orang tua.',
                            'Tab Jurnal Presensi: Membaca tanggal pertemuan, nama tentor, topik materi yang dipelajari ananda, dan catatan pengajar.',
                            'Tab Galeri Foto: Melihat foto dokumentasi suasana ananda saat belajar di kelas bimbel.',
                            'Di layar smartphone, Anda juga dapat menggunakan navigasi melayang (Floating Bar) di bagian bawah layar.',
                        ],
                    ],
                    [
                        'id' => 'parent-gallery',
                        'title' => '4. Memperbesar Foto Dokumentasi Kelas (Lightbox)',
                        'steps' => [
                            'Buka Tab "Galeri Foto" atau klik banner preview foto di tab pertama.',
                            'Ketuk pada salah satu foto untuk memperbesar tampilan (mode layar penuh / Lightbox Zoom).',
                            'Anda dapat melihat keterangan tanggal, nama tentor pengampu, dan topik materi yang sedang dipelajari.',
                        ],
                    ],
                ],
            ],
        ];

        // FAQ Umum untuk Semua Pengguna
        $faqs = [
            [
                'question' => 'Bagaimana jika lupa kata sandi akun?',
                'answer' => 'Untuk akun Guru dan Siswa/Orang Tua, silakan hubungi Administrator Lembaga Bimbel Anda. Admin dapat melakukan reset kata sandi akun Anda secara instan dari panel manajemen.',
            ],
            [
                'question' => 'Mengapa guru wajib mengunggah foto dokumentasi kelas saat absensi?',
                'answer' => 'Foto dokumentasi kelas merupakan bukti otentik kegiatan belajar tatap muka. Foto ini juga langsung dibagikan secara transparan kepada orang tua murid di Portal Monitoring agar orang tua dapat melihat aktivitas belajar anak di kelas.',
            ],
            [
                'question' => 'Apakah foto yang diunggah akan menghabiskan kuota internet?',
                'answer' => 'Tidak. Aplikasi dilengkapi teknologi Client-Side Auto-Compression yang secara cerdas memperkecil ukuran gambar sebelum dikirimkan ke server, sehingga sangat hemat kuota dan proses upload berlangsung sekejap.',
            ],
            [
                'question' => 'Bagaimana cara orang tua melihat rekap kehadiran anak jika memiliki lebih dari satu anak di bimbel?',
                'answer' => 'Masing-masing anak memiliki kredensial username (NIS) yang unik. Orang tua dapat login menggunakan username masing-masing ananda untuk memantau kehadiran dan foto kelasnya secara mandiri.',
            ],
            [
                'question' => 'Bagaimana cara mengubah foto profil saya?',
                'answer' => 'Klik menu avatar Anda di pojok kanan atas, pilih "Profil Saya", lalu tekan tombol kamera pada foto profil untuk memilih gambar baru Anda. Foto akan otomatis diperbarui di sistem.',
            ],
        ];

        return Inertia::render('HelpCenter/Index', [
            'userRole'     => $user->role,
            'guidesByRole' => $guidesByRole,
            'faqs'         => $faqs,
            'tenant'       => [
                'name'  => $user->tenant?->name ?? 'Lembaga Bimbel',
                'phone' => $user->tenant?->phone ?? '08123456789',
                'email' => $user->tenant?->email ?? 'admin@bimbel.com',
            ],
        ]);
    }
}
