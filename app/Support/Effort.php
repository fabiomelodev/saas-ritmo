<?php

namespace App\Support;

/** Escala de esforço percebido (PSE) e conversões de tempo/ritmo */
class Effort
{
    public const LABELS = [
        1 => 'Muito leve',
        2 => 'Leve',
        3 => 'Leve',
        4 => 'Moderado',
        5 => 'Moderado',
        6 => 'Um pouco intenso',
        7 => 'Intenso',
        8 => 'Muito intenso',
        9 => 'Muito intenso',
        10 => 'Máximo',
    ];

    public static function label(?int $rpe): ?string
    {
        return $rpe ? (self::LABELS[$rpe] ?? null) : null;
    }

    /** "43:00" → 2580, "1:15:30" → 4530, "45" → 2700 (minutos) */
    public static function parseDuration(?string $value): ?int
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d{1,3}(:\d{1,2}){0,2}$/', $value)) {
            return null;
        }

        $parts = array_map('intval', explode(':', $value));

        return match (count($parts)) {
            1 => $parts[0] * 60,
            2 => $parts[0] * 60 + $parts[1],
            3 => $parts[0] * 3600 + $parts[1] * 60 + $parts[2],
        };
    }

    /** 2580 → "43:00", 4530 → "1:15:30" */
    public static function formatDuration(?int $seconds): ?string
    {
        if (! $seconds) {
            return null;
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    public static function parseKm(?string $value): ?float
    {
        $value = str_replace(',', '.', trim((string) $value));

        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    /** @return array{pace: string, speed: string}|null  ex.: ["8:36", "7,0"] */
    public static function paceAndSpeed(?float $km, ?int $seconds): ?array
    {
        if (! $km || ! $seconds) {
            return null;
        }

        $perKm = (int) round($seconds / $km);

        return [
            'pace' => intdiv($perKm, 60).':'.str_pad((string) ($perKm % 60), 2, '0', STR_PAD_LEFT),
            'speed' => number_format($km / ($seconds / 3600), 1, ',', ''),
        ];
    }
}
