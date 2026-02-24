<?php

namespace App\Support;

use Illuminate\Support\Str;

final class TextNormalizer
{
    public static function collapseWhitespace(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return (string) preg_replace('/\s+/u', ' ', $value);
    }

    public static function upper(?string $value): ?string
    {
        $value = static::collapseWhitespace($value);
        if ($value === null || $value === '') {
            return $value;
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Normalize branch code input (e.g. " br- 001 " -> "BR-001").
     */
    public static function branchCode(?string $value): ?string
    {
        $value = static::upper($value);
        if ($value === null || $value === '') {
            return $value;
        }

        $value = (string) preg_replace('/\s*-\s*/u', '-', $value);
        $value = (string) preg_replace('/\s+/u', '', $value);

        return $value;
    }

    /**
     * Title case for person names, preserving only very short acronyms (e.g. "NRS", "LGA", "BMW").
     */
    public static function personName(?string $value): ?string
    {
        return static::titlePreserveAcronyms($value, 3);
    }

    /**
     * Title case for organization/entity names; slightly more permissive for acronyms.
     */
    public static function titleText(?string $value): ?string
    {
        return static::titlePreserveAcronyms($value, 3, [
            'FRSC',
            'LASA',
            'FCT',
            'LGA',
            'NRS',
        ]);
    }

    /**
     * Title case that tries to preserve short acronyms.
     *
     * Examples (max length 3):
     * - "NRS FLEET MANAGER" => "NRS Fleet Manager"
     * - "IBRAHIM MUSA" => "Ibrahim Musa"
     * - "BMW X5" => "BMW X5"
     */
    public static function titlePreserveAcronyms(?string $value, int $maxAcronymLength = 3, array $forceUpper = []): ?string
    {
        $value = static::collapseWhitespace($value);
        if ($value === null || $value === '') {
            return $value;
        }

        $maxAcronymLength = max(2, (int) $maxAcronymLength);

        $forceMap = [];
        foreach ($forceUpper as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }
            $forceMap[mb_strtoupper($item, 'UTF-8')] = true;
        }

        $tokens = preg_split('/\s+/u', $value) ?: [];
        $normalized = array_map(function (string $token) use ($maxAcronymLength, $forceMap): string {
            $parts = preg_split('/(-)/u', $token, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

            $out = '';
            foreach ($parts as $part) {
                if ($part === '-') {
                    $out .= $part;
                    continue;
                }

                $out .= static::titleApostropheCompound($part, $maxAcronymLength, $forceMap);
            }

            return $out;
        }, $tokens);

        return implode(' ', $normalized);
    }

    private static function titleApostropheCompound(string $value, int $maxAcronymLength, array $forceMap): string
    {
        if ($value === '') {
            return '';
        }

        $segments = preg_split("/([’'])/u", $value, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $out = '';
        foreach ($segments as $segment) {
            if ($segment === "'" || $segment === '’') {
                $out .= $segment;
                continue;
            }

            $out .= static::titleToken($segment, $maxAcronymLength, $forceMap);
        }

        return $out;
    }

    private static function titleToken(string $token, int $maxAcronymLength, array $forceMap): string
    {
        if ($token === '') {
            return '';
        }

        $upper = mb_strtoupper($token, 'UTF-8');
        if (isset($forceMap[$upper])) {
            return $upper;
        }

        if (preg_match('/^[A-Z0-9]{2,' . $maxAcronymLength . '}$/', $token) === 1) {
            return $upper;
        }

        if (preg_match('/^[0-9]+$/', $token) === 1) {
            return $token;
        }

        return Str::title(mb_strtolower($token, 'UTF-8'));
    }
}
