<?php

namespace App\Enums;

enum DayOfWeek: int
{
    case SUNDAY = 0;
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;

    /**
     * Get the Indonesian name of the day
     */
    public function label(): string
    {
        return match ($this) {
            self::SUNDAY => 'Minggu',
            self::MONDAY => 'Senin',
            self::TUESDAY => 'Selasa',
            self::WEDNESDAY => 'Rabu',
            self::THURSDAY => 'Kamis',
            self::FRIDAY => 'Jumat',
            self::SATURDAY => 'Sabtu',
        };
    }

    /**
     * Get the English name of the day
     */
    public function name(): string
    {
        return match ($this) {
            self::SUNDAY => 'Sunday',
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
        };
    }

    /**
     * Get all days as array
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'name' => $case->name(),
            'label' => $case->label(),
        ], self::cases());
    }

    /**
     * Check if the day is a weekend
     */
    public function isWeekend(): bool
    {
        return in_array($this, [self::SATURDAY, self::SUNDAY]);
    }

    /**
     * Check if the day is a weekday
     */
    public function isWeekday(): bool
    {
        return !$this->isWeekend();
    }
}
