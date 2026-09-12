<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    /**
     * Tampilkan Landing Page Resmi Bimbel No Name
     */
    public function index(Request $request): Response
    {
        // Cari profil lembaga Bimbel No Name di database
        $bimbel = Tenant::where('slug', 'bimbel-no-name')->first();

        $bimbelData = [
            'name'       => $bimbel ? $bimbel->name : 'Bimbel No Name',
            'tagline'    => 'Bimbingan Belajar Modern Berbasis Prestasi & Terpantau Real-Time',
            'phone'      => $bimbel ? $bimbel->phone : '0812-3456-7890',
            'email'      => $bimbel ? $bimbel->email : 'info@bimbelnoname.com',
            'city'       => $bimbel ? $bimbel->city : 'Jakarta Selatan',
            'address'    => $bimbel ? $bimbel->address : 'Jl. Pendidikan Utama No. 88, Gedung Bimbel No Name Lantai 1 & 2',
            'brand_color'=> $bimbel ? $bimbel->brand_color : '#F97316',
        ];

        // Slider interaktif profil Bimbel No Name (Nantinya gambar & teks dapat diatur di dashboard admin)
        $slides = [
            [
                'id'          => 1,
                'badge'       => '🎓 Lembaga Bimbingan Belajar Unggulan',
                'title'       => 'Raih Prestasi Akademik Impian Bersama Bimbel No Name',
                'subtitle'    => 'Pendampingan belajar intensif dengan tutor berdedikasi tinggi, modul pembelajaran mutakhir, dan ekosistem pemantauan presensi siswa yang transparan bagi orang tua.',
                'image'       => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=1920&auto=format&fit=crop',
                'primary_btn' => 'Daftar Siswa Baru',
                'primary_url' => '#program-belajar',
                'sec_btn'     => 'Pelajari Sistem Presensi',
                'sec_url'     => '#fitur-presensi',
                'accent'      => 'from-orange-500 to-amber-500',
            ],
            [
                'id'          => 2,
                'badge'       => '📲 Terhubung Langsung ke Orang Tua',
                'title'       => 'Ketenangan Hati Orang Tua: Kehadiran Siswa Terpantau Real-Time',
                'subtitle'    => 'Di Bimbel No Name, setiap ananda tiba dan pulang dari kelas belajar, notifikasi resmi WhatsApp langsung terkirim otomatis ke ponsel Ayah dan Bunda tanpa khawatir.',
                'image'       => 'https://images.unsplash.com/photo-1577495508048-b635879837f1?q=80&w=1920&auto=format&fit=crop',
                'primary_btn' => 'Layanan Pantau Ortu',
                'primary_url' => '#fitur-presensi',
                'sec_btn'     => 'Hubungi WhatsApp Bimbel',
                'sec_url'     => 'https://wa.me/6281234567890',
                'accent'      => 'from-amber-500 to-orange-600',
            ],
            [
                'id'          => 3,
                'badge'       => '👩‍🏫 Guru & Tutor Berpengalaman',
                'title'       => 'Pengajar Fokus Membimbing dengan Dukungan Presensi Digital Cepat',
                'subtitle'    => 'Tutor kami mengajar dengan sepenuh hati tanpa terbebani absen manual. Kelas berlangsung aktif, interaktif, dan materi tersampaikan secara tuntas.',
                'image'       => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1920&auto=format&fit=crop',
                'primary_btn' => 'Lihat Pilihan Program',
                'primary_url' => '#program-belajar',
                'sec_btn'     => 'Masuk Portal Guru & Siswa',
                'sec_url'     => '/login',
                'accent'      => 'from-orange-600 to-amber-400',
            ],
        ];

        // Statistik Bimbel No Name
        $stats = [
            'total_students'   => '350+',
            'graduation_rate'  => '94.8%',
            'tutors_count'     => '24 Tutor Ahli',
            'notif_speed'      => '< 1 Detik via WA',
        ];

        // Program Belajar di Bimbel No Name
        $programs = [
            [
                'id'       => 'sd',
                'name'     => 'Bimbel SD Juara',
                'level'    => 'Kelas 4, 5, 6 SD',
                'desc'     => 'Fokus pemahaman konsep dasar Matematika, IPA, dan Bahasa Indonesia dengan pendekatan menyenangkan dan bebas stres.',
                'sessions' => '3x Seminggu &bull; 90 Menit / Sesi',
                'tag'      => 'Fondasi Kuat',
            ],
            [
                'id'       => 'smp',
                'name'     => 'Bimbel SMP Akselerasi',
                'level'    => 'Kelas 7, 8, 9 SMP',
                'desc'     => 'Pendalaman materi kurikulum, bimbingan tugas harian, dan persiapan sukses masuk SMA/SMK favorit incaran.',
                'sessions' => '3x Seminggu &bull; 90 Menit / Sesi',
                'tag'      => 'Siap Ujian',
            ],
            [
                'id'       => 'sma',
                'name'     => 'Bimbel SMA Saintek & Soshum',
                'level'    => 'Kelas 10, 11, 12 SMA',
                'desc'     => 'Pemantapan materi peminatan, bedah konsep mendalam, dan trik cepat penyelesaian soal-soal tingkat lanjut.',
                'sessions' => '4x Seminggu &bull; 105 Menit / Sesi',
                'tag'      => 'Target Nilai A',
            ],
            [
                'id'       => 'utbk',
                'name'     => 'Intensif UTBK-SNBT & Kedinasan',
                'level'    => 'Kelas 12 & Alumni / Gap Year',
                'desc'     => 'Simulasi try out berkala, pembahasan penalaran matematika & skolastik (TPS), serta pendampingan pemilihan jurusan PTN.',
                'sessions' => 'Setiap Hari &bull; Tryout Mingguan',
                'tag'      => 'Paling Diminati',
                'highlight'=> true,
            ],
        ];

        return Inertia::render('Landing/Index', [
            'bimbel'    => $bimbelData,
            'slides'    => $slides,
            'stats'     => $stats,
            'programs'  => $programs,
            'user'      => Auth::user() ? Auth::user()->loadMissing('tenant') : null,
        ]);
    }
}
