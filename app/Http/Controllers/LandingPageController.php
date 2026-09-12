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
        $bimbel = Tenant::where('slug', 'bimbel-no-name')->first() ?? Tenant::first();

        $bimbelData = [
            'name'       => $bimbel ? $bimbel->name : 'Bimbel No Name',
            'tagline'    => 'Bimbingan Belajar Modern Berbasis Prestasi & Terpantau Real-Time',
            'phone'      => $bimbel ? $bimbel->phone : '0812-3456-7890',
            'email'      => $bimbel ? $bimbel->email : 'info@bimbelnoname.com',
            'city'       => $bimbel ? $bimbel->city : 'Jakarta Selatan',
            'address'    => $bimbel ? $bimbel->address : 'Jl. Pendidikan Utama No. 88, Gedung Bimbel No Name Lantai 1 & 2',
            'brand_color'=> $bimbel ? $bimbel->brand_color : '#F97316',
            'logo_url'   => $bimbel ? $bimbel->logo_url : '/images/logo_bnn.png',
        ];

        // 3 Slider Interaktif Berfokus pada Kualitas Pendidikan & Pengajaran
        $slides = [
            [
                'id'          => 1,
                'badge'       => 'Standar Pengajaran Unggul',
                'title'       => 'Pendidikan Berkualitas dengan Pendampingan Tutor Berdedikasi',
                'subtitle'    => 'Metode pengajaran interaktif dan mendalam untuk membangun pemahaman konsep secara tuntas, bukan sekadar menghafal rumus.',
                'image'       => '/images/slide_quality_tutor.jpg',
                'tag'         => 'Tutor Berpengalaman',
            ],
            [
                'id'          => 2,
                'badge'       => 'Fasilitas & Suasana Nyaman',
                'title'       => 'Lingkungan Belajar Kondusif untuk Fokus Maksimal',
                'subtitle'    => 'Kelas berukuran kecil didukung sarana belajar modern menciptakan suasana yang bersahabat dan memacu semangat belajar siswa.',
                'image'       => '/images/slide_modern_class.jpg',
                'tag'         => 'Kelas Kecil & Terarah',
            ],
            [
                'id'          => 3,
                'badge'       => 'Evaluasi & Capaian Prestasi',
                'title'       => 'Bimbingan Terarah Menuju Prestasi Akademik Terbaik',
                'subtitle'    => 'Pemantauan perkembangan belajar yang terukur dan berkala membantu siswa meraih potensi terbaik dan percaya diri dalam menghadapi ujian.',
                'image'       => '/images/slide_student_success.jpg',
                'tag'         => 'Capaian Terukur',
            ],
        ];

        $stats = [];
        $programs = [];

        return Inertia::render('Landing/Index', [
            'bimbel'    => $bimbelData,
            'slides'    => $slides,
            'stats'     => $stats,
            'programs'  => $programs,
            'user'      => Auth::user() ? Auth::user()->loadMissing('tenant') : null,
        ]);
    }
}
