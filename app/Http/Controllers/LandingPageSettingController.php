<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantLandingSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageSettingController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman CMS Pengaturan Konten Landing Page
     */
    public function edit(Request $request): Response
    {
        $user = Auth::user();
        if (!$user->isAdminBimbel() && !$user->isSuperAdmin()) {
            abort(403, 'Akses terbatas untuk Administrator Bimbel.');
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            abort(404, 'Data lembaga bimbel tidak ditemukan.');
        }

        $defaults = TenantLandingSetting::getDefaults();

        $setting = TenantLandingSetting::firstOrCreate(
            ['tenant_id' => $tenant->id],
            $defaults
        );

        // Pastikan seluruh seksi memiliki fallback ke default jika kosong/null
        $formattedSettings = [
            'id'              => $setting->id,
            'navbar_subtitle' => $setting->navbar_subtitle ?: $defaults['navbar_subtitle'],
            'hero_slides'     => !empty($setting->hero_slides) ? $setting->hero_slides : $defaults['hero_slides'],
            'quality_header'  => !empty($setting->quality_header) ? $setting->quality_header : $defaults['quality_header'],
            'quality_items'   => !empty($setting->quality_items) ? $setting->quality_items : $defaults['quality_items'],
            'parent_cta'      => !empty($setting->parent_cta) ? $setting->parent_cta : $defaults['parent_cta'],
            'tentor_cta'      => !empty($setting->tentor_cta) ? $setting->tentor_cta : $defaults['tentor_cta'],
            'contact_section' => !empty($setting->contact_section) ? $setting->contact_section : $defaults['contact_section'],
        ];

        return Inertia::render('LandingPage/Settings', [
            'settings' => $formattedSettings,
            'tenant'   => [
                'id'       => $tenant->id,
                'name'     => $tenant->name,
                'logo_url' => $tenant->logo_url,
                'phone'    => $tenant->phone,
                'phone_2'  => $tenant->phone_2,
            ],
        ]);
    }

    /**
     * Simpan Perubahan Konten & Gambar Landing Page
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdminBimbel() && !$user->isSuperAdmin()) {
            abort(403, 'Akses terbatas untuk Administrator Bimbel.');
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            abort(404, 'Data lembaga bimbel tidak ditemukan.');
        }

        $request->validate([
            'navbar_subtitle'       => ['nullable', 'string', 'max:255'],
            'hero_slides'           => ['required', 'array'],
            'hero_slides.*.badge'   => ['required', 'string', 'max:100'],
            'hero_slides.*.title'   => ['required', 'string', 'max:255'],
            'hero_slides.*.subtitle'=> ['required', 'string', 'max:500'],
            'hero_slides.*.tag'     => ['nullable', 'string', 'max:100'],
            'hero_slide_images.*'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'quality_header'        => ['required', 'array'],
            'quality_header.badge'  => ['required', 'string', 'max:100'],
            'quality_header.title'  => ['required', 'string', 'max:255'],
            'quality_header.subtitle'=> ['required', 'string', 'max:500'],

            'quality_items'         => ['required', 'array'],
            'quality_items.*.title' => ['required', 'string', 'max:255'],
            'quality_items.*.badge' => ['required', 'string', 'max:100'],
            'quality_items.*.desc'  => ['required', 'string', 'max:500'],
            'quality_items.*.points'=> ['required', 'array'],
            'quality_item_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'parent_cta'            => ['required', 'array'],
            'parent_cta.badge'      => ['required', 'string', 'max:100'],
            'parent_cta.title'      => ['required', 'string', 'max:255'],
            'parent_cta.desc'       => ['required', 'string', 'max:500'],
            'parent_cta.button_text'=> ['required', 'string', 'max:100'],
            'parent_cta.button_url' => ['required', 'string', 'max:255'],
            'parent_cta.points'     => ['required', 'array'],
            'parent_cta_image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'tentor_cta'            => ['required', 'array'],
            'tentor_cta.badge'      => ['required', 'string', 'max:100'],
            'tentor_cta.title'      => ['required', 'string', 'max:255'],
            'tentor_cta.desc'       => ['required', 'string', 'max:500'],
            'tentor_cta.button_text'=> ['required', 'string', 'max:100'],
            'tentor_cta.button_url' => ['required', 'string', 'max:255'],
            'tentor_cta.points'     => ['required', 'array'],
            'tentor_cta_image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'contact_section'       => ['nullable', 'array'],
        ]);

        try {
            DB::beginTransaction();

            $setting = TenantLandingSetting::firstOrCreate(
                ['tenant_id' => $tenant->id],
                TenantLandingSetting::getDefaults()
            );

            // 1. Proses Hero Slides & Upload Gambarnya
            $heroSlides = $request->input('hero_slides', []);
            if ($request->hasFile('hero_slide_images')) {
                foreach ($request->file('hero_slide_images') as $idx => $file) {
                    if ($file && isset($heroSlides[$idx])) {
                        $path = $file->store('landing_media', 'public');
                        $heroSlides[$idx]['image'] = Storage::disk('public')->url($path);
                    }
                }
            }

            // 2. Proses Quality Items & Upload Gambarnya
            $qualityItems = $request->input('quality_items', []);
            if ($request->hasFile('quality_item_images')) {
                foreach ($request->file('quality_item_images') as $idx => $file) {
                    if ($file && isset($qualityItems[$idx])) {
                        $path = $file->store('landing_media', 'public');
                        $qualityItems[$idx]['image'] = Storage::disk('public')->url($path);
                    }
                }
            }

            // 3. Proses Parent CTA Image
            $parentCta = $request->input('parent_cta', []);
            if ($request->hasFile('parent_cta_image')) {
                $path = $request->file('parent_cta_image')->store('landing_media', 'public');
                $parentCta['image'] = Storage::disk('public')->url($path);
            }

            // 4. Proses Tentor CTA Image
            $tentorCta = $request->input('tentor_cta', []);
            if ($request->hasFile('tentor_cta_image')) {
                $path = $request->file('tentor_cta_image')->store('landing_media', 'public');
                $tentorCta['image'] = Storage::disk('public')->url($path);
            }

            // 5. Update Database Record
            $setting->update([
                'navbar_subtitle' => $request->input('navbar_subtitle'),
                'hero_slides'     => $heroSlides,
                'quality_header'  => $request->input('quality_header'),
                'quality_items'   => $qualityItems,
                'parent_cta'      => $parentCta,
                'tentor_cta'      => $tentorCta,
                'contact_section' => $request->input('contact_section'),
            ]);

            // Bersihkan Cache
            Cache::forget("tenant_landing_{$tenant->id}");
            Cache::forget("tenant_landing_default");

            DB::commit();

            return back()->with('success', 'Konten landing page berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal memperbarui konten landing page: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Terjadi kendala saat menyimpan konten landing page.',
            ]);
        }
    }

    /**
     * Kembalikan Konten Landing Page ke Default Template
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdminBimbel() && !$user->isSuperAdmin()) {
            abort(403, 'Akses terbatas untuk Administrator Bimbel.');
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            abort(404, 'Data lembaga bimbel tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $defaults = TenantLandingSetting::getDefaults();

            $setting = TenantLandingSetting::firstOrCreate(
                ['tenant_id' => $tenant->id],
                $defaults
            );

            $setting->update($defaults);

            Cache::forget("tenant_landing_{$tenant->id}");
            Cache::forget("tenant_landing_default");

            DB::commit();

            return back()->with('success', 'Konten landing page berhasil direset ke standar bawaan!');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal mereset konten landing page: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return back()->withErrors([
                'error' => 'Gagal mereset konten landing page.',
            ]);
        }
    }
}
