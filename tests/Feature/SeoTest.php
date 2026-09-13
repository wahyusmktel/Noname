<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;
    public function test_sitemap_xml_returns_valid_xml_response(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->assertStringContainsString('<urlset', $response->getContent());
        $this->assertStringContainsString('<loc>', $response->getContent());
        $this->assertStringContainsString('logo_bnn.png', $response->getContent());
    }

    public function test_landing_page_renders_seo_meta_tags_and_schema(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Cek Primary Meta Tags
        $this->assertStringContainsString('<meta name="description"', $content);
        $this->assertStringContainsString('<meta name="keywords"', $content);
        $this->assertStringContainsString('<link rel="canonical"', $content);
        $this->assertStringContainsString('<meta name="robots"', $content);

        // Cek Open Graph & Twitter
        $this->assertStringContainsString('<meta property="og:type"', $content);
        $this->assertStringContainsString('<meta property="og:image"', $content);
        $this->assertStringContainsString('<meta name="twitter:card"', $content);

        // Cek Schema.org JSON-LD
        $this->assertStringContainsString('"@context": "https://schema.org"', $content);
        $this->assertStringContainsString('"@type": "EducationalOrganization"', $content);
        $this->assertStringContainsString('"@type": "WebSite"', $content);
    }

    public function test_robots_txt_exists_and_references_sitemap(): void
    {
        $robotsPath = public_path('robots.txt');
        $this->assertFileExists($robotsPath);

        $content = file_get_contents($robotsPath);
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString('sitemap.xml', $content);
        $this->assertStringContainsString('Disallow: /dashboard', $content);
    }
}
