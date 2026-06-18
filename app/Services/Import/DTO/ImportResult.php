<?php

declare(strict_types=1);

namespace App\Services\Import\DTO;

/**
 * Mutable tally of one import run.
 */
final class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function total(): int
    {
        return $this->created + $this->updated + $this->skipped;
    }

    public function summary(): string
    {
        return sprintf(
            '%d created, %d updated, %d skipped, %d errors',
            $this->created,
            $this->updated,
            $this->skipped,
            count($this->errors),
        );
    }
}
