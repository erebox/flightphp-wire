<?php
declare(strict_types=1);
namespace core\helpers;

use core\controllers\BaseWebController;
use core\controllers\BaseApiController;
use ReflectionClass;

class ControllerScanner {
    /**
     * Scansiona una cartella di controller e li classifica automaticamente
     * in base alla classe base che estendono.
     *
     * @return array{web: class-string[], api: class-string[]}
     */
    public static function scan(string $directory, string $namespace): array {
        $web = [];
        $api = [];
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php');
        foreach ($files as $file) {
            $className = rtrim($namespace, '\\') . '\\' . basename($file, '.php');
            if (!class_exists($className)) {
                continue;
            }
            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }
            if ($reflection->isSubclassOf(BaseApiController::class)) {
                $api[] = $className;
            } elseif ($reflection->isSubclassOf(BaseWebController::class)) {
                $web[] = $className;
            }
            // Controller che non estendono nessuna delle due vengono ignorati: restano manuali.
        }

        return ['web' => $web, 'api' => $api];
    }
}
