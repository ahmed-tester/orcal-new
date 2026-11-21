<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Mpdf\Barcode;

class BarcodeService
{
    /**
     * Generate an inline SVG QR Code from a given string.
     */
    public static function generateQrSvg(string $code, int $scale = 6): string
    {
        $code = trim($code);
        if (empty($code)) {
            return '';
        }

        try {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'outputBase64' => false,
                'eccLevel' => QRCode::ECC_L,
                'scale' => $scale,
                'imageTransparent' => false,
            ]);

            $svg = (new QRCode($options))->render($code);

            if (empty($svg)) {
                return '';
            }

            // Remove XML declaration because SVG is injected directly into HTML
            $svg = (string) preg_replace('/<\?xml[^>]*\?>/u', '', $svg);

            // Standardize voucher presentation: white background, padding, and smooth borders
            return str_replace(
                '<svg',
                '<svg style="background: #ffffff; padding: 6px; border-radius: 8px; border: 1px solid #e5e7eb; display: block; margin: 0 auto; width: 100%; max-width: 220px; height: auto;"',
                $svg,
            );
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }

    /**
     * Generate an inline SVG Barcode (Code 128B) from a given string.
     */
    public static function generateSvg(string $code, int $barHeight = 55, bool $showText = true): string
    {
        $code = trim($code);
        if (empty($code)) {
            return '';
        }

        try {
            $barcodeGenerator = new Barcode;
            $data = $barcodeGenerator->getBarcodeArray($code, 'C128B');

            if (! $data || empty($data['bcode'])) {
                return '';
            }

            $totalWidth = (int) ($data['maxw'] ?? 0);
            $padding = 10;
            $viewBoxWidth = $totalWidth + ($padding * 2);
            $viewBoxHeight = $barHeight + ($showText ? 24 : 6);

            $rects = [];
            $currentX = $padding;

            foreach ($data['bcode'] as $bar) {
                $barWidth = (int) ($bar['w'] ?? 1);
                if (! empty($bar['t'])) {
                    $rects[] = sprintf(
                        '<rect x="%d" y="4" width="%d" height="%d" fill="#111827" />',
                        $currentX,
                        $barWidth,
                        $barHeight
                    );
                }
                $currentX += $barWidth;
            }

            $rectsHtml = implode("\n", $rects);

            $textHtml = '';
            if ($showText) {
                $textY = $barHeight + 18;
                $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
                $textHtml = sprintf(
                    '<text x="50%%" y="%d" text-anchor="middle" font-family="monospace, ui-monospace" font-size="12" font-weight="600" fill="#374151" letter-spacing="1.5">%s</text>',
                    $textY,
                    $safeCode
                );
            }

            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" style="width: 100%%; max-width: 320px; height: auto; display: block; margin: 0 auto; background: #ffffff; padding: 6px; border-radius: 8px; border: 1px solid #e5e7eb;">%s%s</svg>',
                $viewBoxWidth,
                $viewBoxHeight,
                $rectsHtml,
                $textHtml
            );
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }
}
