<?php
declare(strict_types=1);
namespace core\helpers;

class Utils {

    public static function getDevMode(string $h): bool {
        return str_contains(strtolower($h), "localhost");
    }

    /*
        Gestione caricamento file e json
    */

    public static function loadJson(string $fl): array {
        $r = [];
        if ($fl != "") {
            $a = json_decode($fl, true);
            if ($a != null) {
                $r = $a;
            }
        }
        return $r;
    }

    public static function readFile(string $directory, string $fileName, bool $isGz = false): string {
        $fileContent = "";
        $path = realpath($directory.$fileName);
        if ($path !== false && str_starts_with($path, $directory)) {
            $fileContent = file_get_contents($path) ?: "";
        }
        if ($isGz === true && $fileContent !== "") {
            $fileContent = gzdecode($fileContent) ?: "";
        }
        return $fileContent;
    }

    public static function readJson(string $directory, string $fileName, bool $isGz = false): array {
        return self::loadJson(self::readFile($directory, $fileName, $isGz));
    }

    /*
        Gestione creazione nuove immagini
    */

    public static function fitTextInBox($image, string $fontPath, string $text, array $box, bool $drawTextBox = false, $padding = 5, $angle = 0): void {
        $boxX = $box[0];
        $boxY = $box[1];
        $boxWidth = $box[2];
        $boxHeight = $box[3];
        $fontSize = 80; // partiamo da un font grande
        $minFontSize = 5;
        while ($fontSize > $minFontSize) {
            $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);
            $textWidth  = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
            if ($textWidth + 2 * $padding <= $boxWidth && $textHeight + 2 * $padding <= $boxHeight) {
                break;
            }
            $fontSize--;
        }
        $x = $boxX + ($boxWidth - $textWidth) / 2;
        $y = $boxY + ($boxHeight + $textHeight) / 2;
        imagettftext($image, $fontSize, $angle, intval($x), intval($y), imagecolorallocate($image, 56, 32, 4), $fontPath, $text);

        if ($drawTextBox) {
            imagerectangle($image, $boxX, $boxY, $boxX + $boxWidth, $boxY + $boxHeight, imagecolorallocate($image, 0, 255, 0));
            $bbox = imagettfbbox($fontSize, $angle, $fontPath, $text);
            $realX = $x + $bbox[6]; // correzione per allineamento
            $realY = $y + $bbox[7];
            imagerectangle(
                $image,
                $realX,
                $realY,
                $realX + $textWidth,
                $realY + $textHeight,
                imagecolorallocate($image, 255, 0, 0)
            );
        }
    }

    public static function createImageWithText(string $sourceImage, string $fontPath, string $text, int $px, int $py, int $dx, int $dy): string {
        $image = null; //80, 1280, 860, 180
        if (file_exists($sourceImage)) {
            $image = imagecreatefromjpeg($sourceImage);
        }
        if (!$image) {
            $image = imagecreatetruecolor(1024, 1536);
            imagefilledrectangle($image, 0, 0, 1024, 1536, imagecolorallocate($image, 225, 180, 100));
        }
        if (!file_exists($fontPath)) {
            imagestring($image, 5, $px, $py, $text, imagecolorallocate($image, 0, 0, 0));
        } else {
            $parti = explode(',',strtoupper($text),2);
            if (count($parti) == 1) {
                self::fitTextInBox($image, $fontPath, trim($parti[0]), [$px, $py, $dx, $dy]);
            } elseif (count($parti) == 2) {
                self::fitTextInBox($image, $fontPath, trim($parti[0]), [$px, $py, $dx, intval($dy / 2)]);
                self::fitTextInBox($image, $fontPath, trim($parti[1]), [$px, $py + 100, $dx, intval($dy / 2)]);
            }
        }
        ob_start();
        imagejpeg($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        return $imageData;
    }

}
