<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => $validated['email']],
            [
                'locale' => app()->getLocale(),
                'status' => 'subscribed',
            ],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('messages.newsletter_success'),
        ]);
    }
}
