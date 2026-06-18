<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Presenters\BlogPostPresenter;
use App\Http\Presenters\CategoryPresenter;
use App\Http\Presenters\StorePresenter;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Store;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PageController extends Controller
{
    public function about(): Response
    {
        return $this->static('public/pages/Content', 'pages.about');
    }

    public function privacy(): Response
    {
        return $this->static('public/pages/Content', 'pages.privacy');
    }

    public function terms(): Response
    {
        return $this->static('public/pages/Content', 'pages.terms');
    }

    public function howItWorks(): Response
    {
        return $this->static('public/pages/HowItWorks', 'pages.how');
    }

    public function faq(): Response
    {
        return $this->static('public/pages/Faq', 'pages.faq');
    }

    public function contact(): Response
    {
        return $this->static('public/pages/Contact', 'pages.contact', [
            'reachEmail' => config('services.notify_email'),
        ]);
    }

    public function contactSubmit(string $locale, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = config('services.notify_email');
        if (is_string($to) && $to !== '') {
            try {
                Mail::raw(
                    "From: {$data['name']} <{$data['email']}>\n\n{$data['message']}",
                    function ($mail) use ($to, $data): void {
                        $mail->to($to)->subject('[CouponX] Contact form')->replyTo($data['email']);
                    },
                );
            } catch (Throwable $e) {
                Log::warning('Contact form mail failed: '.$e->getMessage());
            }
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('messages.pages.contact.success'),
        ]);
    }

    public function sitemap(): Response
    {
        return Inertia::render('public/pages/SitemapPage', [
            'content' => (array) __('messages.pages.sitemap'),
            'stores' => StorePresenter::collection(
                Store::query()->where('is_active', true)->orderBy('name')->get()
            ),
            'categories' => CategoryPresenter::collection(
                Category::query()->where('is_active', true)->orderBy('position')->get()
            ),
            'posts' => BlogPostPresenter::collection(
                BlogPost::query()->published()->orderByDesc('published_at')->get()
            ),
            'meta' => Seo::meta((string) __('messages.pages.sitemap.title')),
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function static(string $component, string $key, array $extra = []): Response
    {
        /** @var array<string, mixed> $content */
        $content = (array) __('messages.'.$key);
        $title = is_string($content['title'] ?? null) ? $content['title'] : (string) config('app.name');

        return Inertia::render($component, array_merge([
            'content' => $content,
            'meta' => Seo::meta($title, is_string($content['intro'] ?? null) ? $content['intro'] : null),
        ], $extra));
    }
}
