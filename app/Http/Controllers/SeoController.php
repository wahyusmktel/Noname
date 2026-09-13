<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Generate dynamic sitemap.xml for search engines (Google, Bing, etc.)
     */
    public function sitemap(Request $request): Response
    {
        $baseUrl = rtrim(config('app.url') ?: $request->root(), '/');
        $now = now()->toAtomString();

        $urls = [
            [
                'loc'        => $baseUrl . '/',
                'lastmod'    => $now,
                'changefreq' => 'daily',
                'priority'   => '1.0',
                'images'     => [
                    [
                        'loc'   => $baseUrl . '/images/logo_bnn.png',
                        'title' => 'Logo Bimbel No name - Bimbingan Belajar Modern',
                    ],
                    [
                        'loc'   => $baseUrl . '/images/og-banner.png',
                        'title' => 'Bimbel No name - Fasilitas dan Program Belajar Terpadu',
                    ],
                ],
            ],
            [
                'loc'        => $baseUrl . '/login',
                'lastmod'    => $now,
                'changefreq' => 'monthly',
                'priority'   => '0.5',
                'images'     => [],
            ],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($urls as $url) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= '        <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            $xml .= '        <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xml .= '        <priority>' . $url['priority'] . "</priority>\n";

            foreach ($url['images'] as $img) {
                $xml .= "        <image:image>\n";
                $xml .= '            <image:loc>' . htmlspecialchars($img['loc'], ENT_XML1, 'UTF-8') . "</image:loc>\n";
                $xml .= '            <image:title>' . htmlspecialchars($img['title'], ENT_XML1, 'UTF-8') . "</image:title>\n";
                $xml .= "        </image:image>\n";
            }

            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
