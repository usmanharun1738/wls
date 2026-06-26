<?php

namespace App\Enums;

enum IncidentType: string
{
    case Poaching = 'poaching';
    case Snare = 'snare';
    case InjuredAnimal = 'injured_animal';

    /**
     * User-friendly label for USSD menus.
     */
    public function label(): string
    {
        return match ($this) {
            self::Poaching => 'Poaching',
            self::Snare => 'Snare/Trap',
            self::InjuredAnimal => 'Injured Animal',
        };
    }

    /**
     * Map USSD digit input to incident type.
     */
    public static function fromInput(string $input): ?self
    {
        return match ($input) {
            '1' => self::Poaching,
            '2' => self::Snare,
            '3' => self::InjuredAnimal,
            default => null,
        };
    }
}
