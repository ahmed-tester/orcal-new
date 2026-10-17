<?php

namespace App\Services;

class TafqeetService
{
    private static array $ones = [
        0 => '', 1 => 'واحد', 2 => 'اثنان', 3 => 'ثلاثة', 4 => 'أربعة',
        5 => 'خمسة', 6 => 'ستة', 7 => 'سبعة', 8 => 'ثمانية', 9 => 'تسعة',
        10 => 'عشرة', 11 => 'أحد عشر', 12 => 'اثنا عشر', 13 => 'ثلاثة عشر',
        14 => 'أربعة عشر', 15 => 'خمسة عشر', 16 => 'ستة عشر', 17 => 'سبعة عشر',
        18 => 'ثمانية عشر', 19 => 'تسعة عشر',
    ];

    private static array $tens = [
        2 => 'عشرون', 3 => 'ثلاثون', 4 => 'أربعون', 5 => 'خمسون',
        6 => 'ستون', 7 => 'سبعون', 8 => 'ثمانون', 9 => 'تسعون',
    ];

    private static array $hundreds = [
        0 => '', 1 => 'مائة', 2 => 'مئتان', 3 => 'ثلاثمائة', 4 => 'أربعمائة',
        5 => 'خمسمائة', 6 => 'ستمائة', 7 => 'سبعمائة', 8 => 'ثمانمائة', 9 => 'تسعمائة',
    ];

    public static function inArabic(float $number, string $currency = 'ريال سعودي', string $fractionCurrency = 'هللة'): string
    {
        if ($number == 0) {
            return 'صفر ' . $currency;
        }

        $parts = explode('.', number_format($number, 2, '.', ''));
        $integerPart = (int) $parts[0];
        $fractionPart = (int) ($parts[1] ?? 0);

        $text = self::convertNumber($integerPart) . ' ' . $currency;

        if ($fractionPart > 0) {
            $text .= ' و ' . self::convertNumber($fractionPart) . ' ' . $fractionCurrency;
        }

        return 'فقط ' . trim($text) . ' لا غير';
    }

    private static function convertNumber(int $number): string
    {
        if ($number == 0) {
            return '';
        }

        if ($number < 20) {
            return self::$ones[$number];
        }

        if ($number < 100) {
            $unit = $number % 10;
            $ten = (int) ($number / 10);
            return ($unit > 0 ? self::$ones[$unit] . ' و ' : '') . self::$tens[$ten];
        }

        if ($number < 1000) {
            $hundred = (int) ($number / 100);
            $remainder = $number % 100;
            return self::$hundreds[$hundred] . ($remainder > 0 ? ' و ' . self::convertNumber($remainder) : '');
        }

        if ($number < 1000000) {
            $thousands = (int) ($number / 1000);
            $remainder = $number % 1000;
            $thousandsText = '';

            if ($thousands == 1) {
                $thousandsText = 'ألف';
            } elseif ($thousands == 2) {
                $thousandsText = 'ألفان';
            } elseif ($thousands >= 3 && $thousands <= 10) {
                $thousandsText = self::convertNumber($thousands) . ' آلاف';
            } else {
                $thousandsText = self::convertNumber($thousands) . ' ألفاً';
            }

            return $thousandsText . ($remainder > 0 ? ' و ' . self::convertNumber($remainder) : '');
        }

        if ($number < 1000000000) {
            $millions = (int) ($number / 1000000);
            $remainder = $number % 1000000;
            $millionsText = '';

            if ($millions == 1) {
                $millionsText = 'مليون';
            } elseif ($millions == 2) {
                $millionsText = 'مليونان';
            } elseif ($millions >= 3 && $millions <= 10) {
                $millionsText = self::convertNumber($millions) . ' ملايين';
            } else {
                $millionsText = self::convertNumber($millions) . ' مليوناً';
            }

            return $millionsText . ($remainder > 0 ? ' و ' . self::convertNumber($remainder) : '');
        }

        return (string) $number;
    }
}
