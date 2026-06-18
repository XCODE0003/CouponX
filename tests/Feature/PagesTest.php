<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pages(): array
    {
        return [
            'about' => ['/en/about', 'public/pages/Content'],
            'privacy' => ['/en/privacy', 'public/pages/Content'],
            'terms' => ['/en/terms', 'public/pages/Content'],
            'how it works' => ['/en/how-it-works', 'public/pages/HowItWorks'],
            'faq' => ['/en/faq', 'public/pages/Faq'],
            'contact' => ['/en/contact', 'public/pages/Contact'],
        ];
    }

    #[DataProvider('pages')]
    public function test_static_pages_render(string $path, string $component): void
    {
        $this->get($path)->assertInertia(fn (AssertableInertia $page) => $page
            ->component($component)
            ->has('content.title')
            ->has('meta.title')
        );
    }

    public function test_pages_are_localized(): void
    {
        $this->get('/ru/faq')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('content.title', 'Вопросы и ответы')
        );
    }

    public function test_html_sitemap_lists_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/en/sitemap')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/pages/SitemapPage')
            ->has('stores', 7)
            ->has('categories', 11) // 6 top-level + 5 electronics subcategories
        );
    }

    public function test_contact_form_submits(): void
    {
        $this->post('/en/contact', [
            'name' => 'Jane Shopper',
            'email' => 'jane@example.com',
            'message' => 'Love the deals!',
        ])->assertRedirect();
    }

    public function test_contact_form_validates(): void
    {
        $this->post('/en/contact', ['name' => '', 'email' => 'not-an-email', 'message' => ''])
            ->assertSessionHasErrors(['name', 'email', 'message']);
    }
}
