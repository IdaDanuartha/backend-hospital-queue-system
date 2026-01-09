<?php

namespace App\Enums;

enum QueueStatus: string
{
    case WAITING = 'WAITING';
    case CALLED = 'CALLED';
    case SERVING = 'SERVING';
    case DONE = 'DONE';
    case SKIPPED = 'SKIPPED';

    /**
     * Get the Indonesian label
     */
    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Menunggu',
            self::CALLED => 'Dipanggil',
            self::SERVING => 'Sedang Dilayani',
            self::DONE => 'Selesai',
            self::SKIPPED => 'Dilewati',
        };
    }

    /**
     * Get the color for UI display
     */
    public function color(): string
    {
        return match ($this) {
            self::WAITING => 'gray',
            self::CALLED => 'blue',
            self::SERVING => 'yellow',
            self::DONE => 'green',
            self::SKIPPED => 'red',
        };
    }

    /**
     * Get the badge color class (for Tailwind)
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::WAITING => 'bg-gray-100 text-gray-800',
            self::CALLED => 'bg-blue-100 text-blue-800',
            self::SERVING => 'bg-yellow-100 text-yellow-800',
            self::DONE => 'bg-green-100 text-green-800',
            self::SKIPPED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Check if the status is active (not done or skipped)
     */
    public function isActive(): bool
    {
        return !in_array($this, [self::DONE, self::SKIPPED]);
    }

    /**
     * Check if the status is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this, [self::DONE, self::SKIPPED]);
    }

    /**
     * Get all statuses as array
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
