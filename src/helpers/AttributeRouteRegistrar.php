<?php
declare(strict_types=1);
namespace core\helpers;

use flight\Engine;
use core\attributes\Route;
use ReflectionClass;
use ReflectionMethod;

class AttributeRouteRegistrar {

    /**
     * Registra le route di un controller, con eventuale prefisso di path
     * e middleware di default aggiunti a TUTTE le route.
     *
     * @param string[] $defaultMiddlewares Middleware da applicare sempre, in aggiunta a quelli dell'attributo
     * @param string $pathPrefix Prefisso da anteporre al path di ogni route (es. '/api')
     * @param string[] $skipRoutes Route ("METODO /path") da NON registrare perché già dichiarate altrove
     */
    public static function register(Engine $app, string $controllerClass, array $defaultMiddlewares = [], string $pathPrefix = '', array $skipRoutes = []): void {
        $reflection = new ReflectionClass($controllerClass);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                /** @var Route $route */
                $route = $attribute->newInstance();
                $fullPath = rtrim($pathPrefix, '/') . '/' . ltrim($route->path, '/');
                $routeKey = $route->method . ' ' . $fullPath;
                if (in_array($routeKey, $skipRoutes, true)) {
                    continue;
                }
                $flightRoute = $app->route($routeKey, [$controllerClass, $method->getName()]);
                $allMiddlewares = array_unique(array_merge($defaultMiddlewares, $route->middlewares));
                foreach ($allMiddlewares as $middleware) {
                    $flightRoute->addMiddleware($middleware);
                }
            }
        }
    }

    /**
     * Registra più controller, con eventuali middleware e prefisso di path comuni.
     * @param class-string[] $controllerClasses
     * @param string[] $defaultMiddlewares
     * @param string[] $skipRoutes Route ("METODO /path") da NON registrare perché già dichiarate altrove
     */
    public static function registerMany(Engine $app, array $controllerClasses, array $defaultMiddlewares = [], string $pathPrefix = '', array $skipRoutes = []): void {
        foreach ($controllerClasses as $controllerClass) {
            self::register($app, $controllerClass, $defaultMiddlewares, $pathPrefix, $skipRoutes);
        }
    }

    /**
     * Elenca le route ("METODO /path") dichiarate dai controller indicati, senza registrarle.
     * Usato per sapere quali route un sito ha già definito autonomamente.
     *
     * @param class-string[] $controllerClasses
     * @return string[]
     */
    public static function collectRoutes(array $controllerClasses, string $pathPrefix = ''): array {
        $routes = [];
        foreach ($controllerClasses as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(Route::class) as $attribute) {
                    /** @var Route $route */
                    $route = $attribute->newInstance();
                    $fullPath = rtrim($pathPrefix, '/') . '/' . ltrim($route->path, '/');
                    $routes[] = $route->method . ' ' . $fullPath;
                }
            }
        }
        return $routes;
    }

}
