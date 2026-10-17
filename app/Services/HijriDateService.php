<?php

namespace App\Services;

use Carbon\Carbon;
use DateTimeInterface;
use IntlDateFormatter;

class HijriDateService
{
    /**
     * Convert any date to Hijri format.
     */
    public static function toHijri(mixed $date = null, string $format = 'd MMMM yyyy'): string
    {
        $dt = self::resolveDateTime($date);
        if (!$dt) {
            return '—';
        }

        try {
            if (class_exists(IntlDateFormatter::class)) {
                $formatter = new IntlDateFormatter(
                    'ar_SA@calendar=islamic-umalqura',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::NONE,
                    'Asia/Riyadh',
                    IntlDateFormatter::TRADITIONAL,
                    $format
                );
                $formatted = $formatter->format($dt);
                if ($formatted) {
                    return $formatted . ' هـ';
                }
            }
        } catch (\Throwable) {
            // Fallback if Intl fails
        }

        return self::fallbackHijri($dt);
    }

    /**
     * Format Hijri in numeric format: yyyy/MM/dd.
     */
    public static function toHijriNumeric(mixed $date = null): string
    {
        $dt = self::resolveDateTime($date);
        if (!$dt) {
            return '—';
        }

        try {
            if (class_exists(IntlDateFormatter::class)) {
                $formatter = new IntlDateFormatter(
                    'en_US@calendar=islamic-umalqura',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::NONE,
                    'Asia/Riyadh',
                    IntlDateFormatter::TRADITIONAL,
                    'yyyy/MM/dd'
                );
                $formatted = $formatter->format($dt);
                if ($formatted) {
                    return $formatted . ' هـ';
                }
            }
        } catch (\Throwable) {
        }

        return self::fallbackHijri($dt);
    }

    /**
     * Format Hijri in clean standard format: yyyy/MM/dd (without suffix, ideal for DB storage & sorting).
     */
    public static function toHijriStandard(mixed $date = null): ?string
    {
        $dt = self::resolveDateTime($date);
        if (!$dt) {
            return null;
        }

        try {
            if (class_exists(IntlDateFormatter::class)) {
                $formatter = new IntlDateFormatter(
                    'en_US@calendar=islamic-umalqura',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::NONE,
                    'Asia/Riyadh',
                    IntlDateFormatter::TRADITIONAL,
                    'yyyy/MM/dd'
                );
                $formatted = $formatter->format($dt);
                if ($formatted) {
                    return trim($formatted);
                }
            }
        } catch (\Throwable) {
        }

        $fallback = self::fallbackHijri($dt);
        return str_replace([' هـ', ' '], '', $fallback);
    }

    /**
     * Return dual date: Gregorian with Hijri counterpart.
     * Example: "2026/10/11 م (1448/04/30 هـ)"
     */
    public static function formatDual(mixed $date = null, bool $withDayName = false): string
    {
        $dt = self::resolveDateTime($date);
        if (!$dt) {
            return '—';
        }

        $carbon = Carbon::instance($dt)->locale('ar');
        $gregorian = $withDayName 
            ? $carbon->translatedFormat('l d F Y') . ' م'
            : $carbon->format('Y/m/d') . ' م';

        $hijri = self::toHijriNumeric($dt);

        return "{$gregorian} — {$hijri}";
    }

    /**
     * Resolve date to DateTimeInterface.
     */
    private static function resolveDateTime(mixed $date): ?DateTimeInterface
    {
        if (empty($date)) {
            return new \DateTime('now', new \DateTimeZone('Asia/Riyadh'));
        }

        if ($date instanceof DateTimeInterface) {
            return $date;
        }

        try {
            return new \DateTime((string) $date, new \DateTimeZone('Asia/Riyadh'));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Mathematical fallback if Intl is unavailable.
     */
    private static function fallbackHijri(DateTimeInterface $date): string
    {
        $timestamp = $date->getTimestamp();
        $gregorianYear = (int) date('Y', $timestamp);
        $gregorianMonth = (int) date('m', $timestamp);
        $gregorianDay = (int) date('d', $timestamp);

        $jd = gregoriantojd($gregorianMonth, $gregorianDay, $gregorianYear);
        $l = $jd - 1948440 + 10632;
        $n = (int) (($l - 1) / 10631);
        $l = $l - 10631 * $n + 354;
        $j = ((int) ((10985 - $l) / 5316)) * ((int) ((50 * $l) / 17719)) + ((int) ($l / 5670)) * ((int) ((43 * $l) / 15238));
        $l = $l - ((int) ((30 - $j) / 15)) * ((int) ((17719 * $j) / 50)) - ((int) ($j / 16)) * ((int) ((15238 * $j) / 43)) + 29;
        $m = (int) ((24 * $l) / 709);
        $d = $l - (int) ((709 * $m) / 24);
        $y = 30 * $n + $j - 30;

        return sprintf('%04d/%02d/%02d هـ', $y, $m, $d);
    }
}
