<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantLandingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        $defaults = TenantLandingSetting::getDefaults();

        $landingSetting = null;
        if ($bimbel) {
            $landingSetting = Cache::remember("tenant_landing_{$bimbel->id}", 3600, function () use ($bimbel) {
                return $bimbel->landingSetting;
            });
        }

        $bimbelData = [
            'name'            => $bimbel ? $bimbel->name : 'Bimbel No Name',
            'tagline'         => $bimbel && $bimbel->tagline ? $bimbel->tagline : 'Bimbingan Belajar Modern Berbasis Prestasi & Terpantau Real-Time',
            'phone'           => $bimbel ? $bimbel->phone : '0812-3456-7890',
            'phone_2'         => $bimbel ? $bimbel->phone_2 : null,
            'whatsapp_sender' => $bimbel ? $bimbel->whatsapp_sender : null,
            'email'           => $bimbel ? $bimbel->email : 'info@bimbelnoname.com',
            'website'         => $bimbel ? $bimbel->website : null,
            'city'            => $bimbel ? $bimbel->city : 'Jakarta Selatan',
            'address'         => $bimbel ? $bimbel->address : 'Jl. Pendidikan Utama No. 88, Gedung Bimbel No Name Lantai 1 & 2',
            'operating_hours' => $bimbel && $bimbel->operating_hours ? $bimbel->operating_hours : 'Senin - Sabtu: 08:00 - 20:00 WIB',
            'brand_color'     => $bimbel ? $bimbel->brand_color : '#F97316',
            'logo_url'        => $bimbel ? $bimbel->logo_url : '/images/logo_bnn.png',
            'tiktok_url'      => $bimbel ? $bimbel->tiktok_url : null,
            'instagram_url'   => $bimbel ? $bimbel->instagram_url : null,
            'youtube_url'     => $bimbel ? $bimbel->youtube_url : null,
            'facebook_url'    => $bimbel ? $bimbel->facebook_url : null,
        ];

        // Ambil data dinamis atau fallback ke standar bawaan
        $slides         = ($landingSetting && !empty($landingSetting->hero_slides)) ? $landingSetting->hero_slides : $defaults['hero_slides'];
        $qualityHeader  = ($landingSetting && !empty($landingSetting->quality_header)) ? $landingSetting->quality_header : $defaults['quality_header'];
        $qualitySlides  = ($landingSetting && !empty($landingSetting->quality_items)) ? $landingSetting->quality_items : $defaults['quality_items'];
        $parentCta      = ($landingSetting && !empty($landingSetting->parent_cta)) ? $landingSetting->parent_cta : $defaults['parent_cta'];
        $tentorCta      = ($landingSetting && !empty($landingSetting->tentor_cta)) ? $landingSetting->tentor_cta : $defaults['tentor_cta'];
        $navbarSubtitle = ($landingSetting && !empty($landingSetting->navbar_subtitle)) ? $landingSetting->navbar_subtitle : $defaults['navbar_subtitle'];

        $stats = [];
        $programs = [];

        return Inertia::render('Landing/Index', [
            'bimbel'         => $bimbelData,
            'slides'         => $slides,
            'qualityHeader'  => $qualityHeader,
            'qualitySlides'  => $qualitySlides,
            'parentCta'      => $parentCta,
            'tentorCta'      => $tentorCta,
            'navbarSubtitle' => $navbarSubtitle,
            'stats'          => $stats,
            'programs'       => $programs,
            'user'           => Auth::user() ? Auth::user()->loadMissing('tenant') : null,
        ]);
    }
}
