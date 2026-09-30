<?php
declare(strict_types=1);
namespace core\helpers;

class Utils {

    public static function getDevMode(string $h): bool {
        return str_contains(strtolower($h), "localhost");
    }

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
        if (str_starts_with($path,  $directory)) {
            $fileContent = file_get_contents($path);
        }
        return ($isGz === true) ? gzdecode($fileContent) : $fileContent;
    }

    public static function readJson(string $directory, string $fileName, bool $isGz = false): array {
        return self::loadJson(self::readFile($directory, $fileName, $isGz));
    }

}
