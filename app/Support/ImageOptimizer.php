<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Converts uploaded raster images to WebP (and downscales oversized ones) for
 * faster loads / better Core Web Vitals. Falls back to storing the original if
 * GD/WebP is unavailable. SVG is intentionally not handled (rejected upstream).
 */
final class ImageOptimizer
{
    public static function storeAsWebp(TemporaryUploadedFile $file, string $directory, int $maxWidth = 800, string $disk = 'public'): ?string
    {
        if (! function_exists('imagewebp')) {
            return self::fallback($file, $directory, $disk);
        }

        $image = self::fromFile($file->getRealPath(), (string) $file->getMimeType());
        if ($image === null) {
            return self::fallback($file, $directory, $disk);
        }

        $image = self::downscale($image, $maxWidth);

        ob_start();
        imagewebp($image, null, 82);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        $name = trim($directory, '/').'/'.bin2hex(random_bytes(16)).'.webp';
        Storage::disk($disk)->put($name, $contents);

        return $name;
    }

    private static function fallback(TemporaryUploadedFile $file, string $directory, string $disk): ?string
    {
        $stored = $file->store($directory, $disk);

        return is_string($stored) ? $stored : null;
    }

    private static function fromFile(string $path, string $mime): ?GdImage
    {
        $image = match (true) {
            str_contains($mime, 'png') => @imagecreatefrompng($path),
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
            str_contains($mime, 'webp') => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            return null;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private static function downscale(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $newHeight = max(1, (int) round($height * ($maxWidth / $width)));
        $resized = imagecreatetruecolor(max(1, $maxWidth), $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
