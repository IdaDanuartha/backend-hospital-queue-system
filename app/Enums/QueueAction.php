<?php

namespace App\Enums;

enum QueueAction: string
{
    case CALL_NEXT = 'CALL_NEXT';
    case RECALL = 'RECALL';
    case SKIP = 'SKIP';
    case START_SERVICE = 'START_SERVICE';
    case FINISH = 'FINISH';
    case CANCEL = 'CANCEL';

    /**
     * Get the Indonesian label
     */
    public function label(): string
    {
        return match ($this) {
            self::CALL_NEXT => 'Panggil Antrian Berikutnya',
            self::RECALL => 'Panggil Ulang',
            self::SKIP => 'Lewati Antrian',
            self::START_SERVICE => 'Mulai Pelayanan',
            self::FINISH => 'Selesai',
            self::CANCEL => 'Batalkan Antrian',
        };
    }

    /**
     * Get the short label
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::CALL_NEXT => 'Panggil',
            self::RECALL => 'Panggil Ulang',
            self::SKIP => 'Lewati',
            self::START_SERVICE => 'Mulai',
            self::FINISH => 'Selesai',
            self::CANCEL => 'Batalkan',
        };
    }

    /**
     * Get the icon name (for UI)
     */
    public function icon(): string
    {
        return match ($this) {
            self::CALL_NEXT => 'bell',
            self::RECALL => 'refresh',
            self::SKIP => 'forward',
            self::START_SERVICE => 'play',
            self::FINISH => 'check',
            self::CANCEL => 'x-circle',
        };
    }

    /**
     * Get the color for UI display
     */
    public function color(): string
    {
        return match ($this) {
            self::CALL_NEXT => 'blue',
            self::RECALL => 'orange',
            self::SKIP => 'red',
            self::START_SERVICE => 'green',
            self::FINISH => 'purple',
            self::CANCEL => 'gray',
        };
    }

    /**
     * Get the resulting status after this action
     */
    public function resultingStatus(): QueueStatus
    {
        return match ($this) {
            self::CALL_NEXT => QueueStatus::CALLED,
            self::RECALL => QueueStatus::CALLED,
            self::SKIP => QueueStatus::SKIPPED,
            self::START_SERVICE => QueueStatus::SERVING,
            self::FINISH => QueueStatus::DONE,
            self::CANCEL => QueueStatus::CANCELLED,
        };
    }

    /**
     * Get all actions as array
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'short_label' => $case->shortLabel(),
            'icon' => $case->icon(),
            'color' => $case->color(),
        ], self::cases());
    }
}
