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
     */
    public static function register(Engine $app, string $controllerClass, array $defaultMiddlewares = [], string $pathPrefix = ''): void {
        $reflection = new ReflectionClass($controllerClass);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                /** @var Route $route */
                $route = $attribute->newInstance();
                $fullPath = rtrim($pathPrefix, '/') . '/' . ltrim($route->path, '/');
                $flightRoute = $app->route(
                    $route->method . ' ' . $fullPath,
                    [$controllerClass, $method->getName()]
                );
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
     */
    public static function registerMany(Engine $app, array $controllerClasses, array $defaultMiddlewares = [], string $pathPrefix = ''): void {
        foreach ($controllerClasses as $controllerClass) {
            self::register($app, $controllerClass, $defaultMiddlewares, $pathPrefix);
        }
    }

}
