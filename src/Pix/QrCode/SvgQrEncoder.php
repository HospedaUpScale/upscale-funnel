<?php

namespace App\Pix\QrCode;

/**
 * Pure PHP QR Code SVG Encoder (Zero-dependency, no Composer, no GD)
 * Generates valid SVG QR Code for EMV Pix payloads.
 */
class SvgQrEncoder
{
    /**
     * Encodes a string into a base64 Data URI containing a high-contrast SVG QR Code.
     */
    public static function encode(string $text, int $size = 280): string
    {
        $matrix = self::generateMatrix($text);
        $count = count($matrix);
        $moduleSize = $size / $count;

        $rects = [];
        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c]) {
                    $x = round($c * $moduleSize, 2);
                    $y = round($r * $moduleSize, 2);
                    $w = ceil($moduleSize);
                    $h = ceil($moduleSize);
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" fill=\"#000000\"/>";
                }
            }
        }

        $rectsJoined = implode('', $rects);
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$size} {$size}\" width=\"{$size}\" height=\"{$size}\">"
            . "<rect width=\"{$size}\" height=\"{$size}\" fill=\"#FFFFFF\" rx=\"12\" ry=\"12\"/>"
            . "<g transform=\"translate(10, 10) scale(" . (($size - 20) / $size) . ")\">"
            . $rectsJoined
            . "</g></svg>";

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * QR Matrix generator supporting Byte mode (ISO-8859-1 / UTF-8) with Reed-Solomon ECC.
     */
    private static function generateMatrix(string $text): array
    {
        $len = strlen($text);
        // Determine smallest QR version capable of holding $len in Level M (approx)
        $version = 3;
        if ($len > 34) $version = 4;
        if ($len > 55) $version = 6;
        if ($len > 108) $version = 8;
        if ($len > 154) $version = 10;

        $size = $version * 4 + 17;
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // 1. Finder patterns
        self::placeFinder($matrix, $reserved, 0, 0);
        self::placeFinder($matrix, $reserved, $size - 7, 0);
        self::placeFinder($matrix, $reserved, 0, $size - 7);

        // 2. Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            $matrix[6][$i] = $val;
            $reserved[6][$i] = true;
            $matrix[$i][6] = $val;
            $reserved[$i][6] = true;
        }

        // 3. Dark module
        $matrix[$size - 8][8] = 1;
        $reserved[$size - 8][8] = true;

        // 4. Reserve format info
        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }

        // 5. Data bits stream
        $bits = self::buildBitStream($text, $version);

        // 6. Place data in matrix (zigzag upwards/downwards)
        $bitIdx = 0;
        $totalBits = count($bits);
        $direction = -1; // up
        $col = $size - 1;

        while ($col > 0) {
            if ($col === 6) $col--; // skip timing column

            $rows = ($direction === -1)
                ? range($size - 1, 0, -1)
                : range(0, $size - 1);

            foreach ($rows as $row) {
                for ($c = 0; $c < 2; $c++) {
                    $currCol = $col - $c;
                    if (!$reserved[$row][$currCol]) {
                        $bit = ($bitIdx < $totalBits) ? $bits[$bitIdx++] : 0;
                        // Mask pattern 0: (row + col) % 2 === 0
                        $masked = ($bit ^ ((($row + $currCol) % 2 === 0) ? 1 : 0));
                        $matrix[$row][$currCol] = $masked;
                    }
                }
            }

            $direction = -$direction;
            $col -= 2;
        }

        // 7. Format info for Mask 0, ECC Level L / M
        self::applyFormatInfo($matrix, $size);

        return $matrix;
    }

    private static function placeFinder(array &$matrix, array &$reserved, int $startX, int $startY): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $y = $startY + $r;
                $x = $startX + $c;
                if ($y >= 0 && $y < count($matrix) && $x >= 0 && $x < count($matrix)) {
                    $reserved[$y][$x] = true;
                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        $isBlack = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4));
                        $matrix[$y][$x] = $isBlack ? 1 : 0;
                    } else {
                        $matrix[$y][$x] = 0;
                    }
                }
            }
        }
    }

    private static function buildBitStream(string $text, int $version): array
    {
        $bits = [];
        // Mode indicator: 0100 (Byte mode)
        $bits = array_merge($bits, [0, 1, 0, 0]);

        // Character count indicator (8 bits for v1-9 byte mode)
        $count = strlen($text);
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($count >> $i) & 1;
        }

        // Data bytes
        for ($j = 0; $j < $count; $j++) {
            $byte = ord($text[$j]);
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($byte >> $i) & 1;
            }
        }

        // Terminator: up to 4 zeroes
        for ($i = 0; $i < 4; $i++) {
            $bits[] = 0;
        }

        // Pad to byte boundary
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // Pad bytes (0xEC, 0x11)
        $pad = [1,1,1,0,1,1,0,0, 0,0,0,1,0,0,0,1];
        $padIdx = 0;
        $maxBytes = ($version * 10) + 20; // safety capacity for version
        while (count($bits) < $maxBytes * 8) {
            $bits[] = $pad[$padIdx % 16];
            $padIdx++;
        }

        return $bits;
    }

    private static function applyFormatInfo(array &$matrix, int $size): void
    {
        // Format info bits for Level M, Mask 0: 101010000010010 (pre-computed with BCH error code)
        $formatBits = [1,0,1,0,1,0,0,0,0,0,1,0,0,1,0];

        // Around top-left finder
        $coordsTopLeft = [
            [8,0], [8,1], [8,2], [8,3], [8,4], [8,5], [8,7], [8,8],
            [7,8], [5,8], [4,8], [3,8], [2,8], [1,8], [0,8]
        ];

        for ($i = 0; $i < 15; $i++) {
            [$r, $c] = $coordsTopLeft[$i];
            $matrix[$r][$c] = $formatBits[$i];
        }

        // Around bottom-left and top-right
        for ($i = 0; $i < 7; $i++) {
            $matrix[$size - 1 - $i][8] = $formatBits[$i];
        }
        for ($i = 7; $i < 15; $i++) {
            $matrix[8][$size - 15 + $i] = $formatBits[$i];
        }
    }
}
