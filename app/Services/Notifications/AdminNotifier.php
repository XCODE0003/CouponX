<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Fans out operational alerts (import failures, partner API outages, cron
 * errors) to Telegram, email and the log. Every channel is best-effort and
 * isolated so a broken channel never masks the original incident.
 */
class AdminNotifier
{
    public function error(string $subject, string $message): void
    {
        Log::error("[CouponX] {$subject}: {$message}");

        $this->toTelegram("\u{26A0}\u{FE0F} {$subject}\n\n{$message}");
        $this->toEmail($subject, $message);
    }

    public function info(string $subject, string $message): void
    {
        Log::info("[CouponX] {$subject}: {$message}");

        $this->toTelegram("\u{2139}\u{FE0F} {$subject}\n\n{$message}");
    }

    private function toTelegram(string $text): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! is_string($token) || $token === '' || ! is_scalar($chatId) || (string) $chatId === '') {
            return;
        }

        try {
            Http::asForm()->timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                ['chat_id' => (string) $chatId, 'text' => $text, 'disable_web_page_preview' => true],
            );
        } catch (Throwable $e) {
            Log::warning('Telegram notification failed: '.$e->getMessage());
        }
    }

    private function toEmail(string $subject, string $message): void
    {
        $to = config('services.notify_email');

        if (! is_string($to) || $to === '') {
            return;
        }

        try {
            Mail::raw($message, function ($mail) use ($to, $subject): void {
                $mail->to($to)->subject("[CouponX] {$subject}");
            });
        } catch (Throwable $e) {
            Log::warning('Email notification failed: '.$e->getMessage());
        }
    }
}
