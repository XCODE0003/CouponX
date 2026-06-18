<?php

use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\CategoryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\StoreController;
use App\Http\Controllers\RedirectController;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

// Cloaked affiliate redirects (locale-agnostic; no raw affiliate URLs in HTML).
Route::get('/go/{store:slug}', [RedirectController::class, 'store'])->name('go.store');
Route::get('/out/{coupon}', [RedirectController::class, 'coupon'])->name('out.coupon');

// SEO: sitemap & robots.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', function () {
    $body = implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin',
        'Disallow: /go/',
        'Disallow: /out/',
        '',
        'Sitemap: '.url('/sitemap.xml'),
        '',
    ]);

    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// Root → detected/default localized home.
Route::get('/', fn () => redirect()->route('home'));

// Localized public site: /{locale}/... (locale is resolved by SetLocale in the web group).
Route::prefix('{locale}')
    ->where(['locale' => Locales::pattern()])
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/store/{store}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/category/{category}', [CategoryController::class, 'show'])->name('categories.show');
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');
        Route::get('/search', SearchController::class)->name('search');
        Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.subscribe');

        // Static / informational pages.
        Route::get('/about', [PageController::class, 'about'])->name('pages.about');
        Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
        Route::post('/contact', [PageController::class, 'contactSubmit'])->name('pages.contact.submit');
        Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
        Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
        Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('pages.how');
        Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');
        Route::get('/sitemap', [PageController::class, 'sitemap'])->name('pages.sitemap');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
