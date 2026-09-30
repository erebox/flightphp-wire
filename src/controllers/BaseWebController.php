<?php
declare(strict_types=1);
namespace core\controllers;

use core\attributes\Route;
use ReflectionClass;
use ReflectionMethod;

/**
 * Classe marcatore: i controller che estendono questa vengono
 * censiti automaticamente come controller "web" (route pubbliche, no apikey).
 */
abstract class BaseWebController extends BaseController {

    /**
     * Elenca le pagine navigabili di un controller leggendo gli attributi Route:
     * solo GET, senza parametri di path e non marcate hidden.
     * L'etichetta è il summary della route, o il path se il summary è vuoto.
     *
     * @param class-string $controllerClass
     * @return array<int, array{path: string, label: string, description: string}>
     */
    protected function pageLinks(string $controllerClass): array {
        $links = [];
        $reflection = new ReflectionClass($controllerClass);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                /** @var Route $route */
                $route = $attribute->newInstance();
                if ($route->hidden || !in_array('GET', explode('|', $route->method), true) || str_contains($route->path, '@')) {
                    continue;
                }
                $links[] = [
                    'path'        => $route->path,
                    'label'       => $route->summary !== '' ? $route->summary : ltrim($route->path, '/'),
                    'description' => $route->description,
                ];
            }
        }
        usort($links, fn($a, $b) => strcasecmp($a['label'], $b['label']));
        return $links;
    }

    /**
     * Renderizza la pagina con i link alle pagine di un controller (di default quello corrente).
     * Usa la vista 'pagelinks.html' del sito se presente in app/views, altrimenti quella della libreria.
     *
     * @param class-string|null $controllerClass
     */
    protected function renderPageLinks(string $title, ?string $controllerClass = null): void {
        $name = 'links';
        $appViewFile = rtrim((string) $this->get('flight.views.path'), DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR . $name . '.html';
        $view = file_exists($appViewFile) ? $name : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $name . '.html';
        $this->render($view, ['title' => $title, 'links' => $this->pageLinks($controllerClass ?? static::class)]);
    }

}
