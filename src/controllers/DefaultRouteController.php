<?php
declare(strict_types=1);
namespace core\controllers;

use core\attributes\Route;
use core\helpers\ControllerScanner;
use core\helpers\OpenApiGenerator;

/**
 * Controller di default: fornisce homepage e Swagger UI base senza che
 * il sito debba dichiararli. Bootstrap::init() registra sempre queste route,
 * ma salta quelle il cui path+metodo è già dichiarato da un controller del sito
 * (es. un proprio DefaultRouteController con solo la route '/' sovrascrive solo quella,
 * lasciando attive le altre come /api e /api/openapi).
 *
 * Le viste (index.html, swagger.html) usate sono quelle del sito, se presenti
 * in app/views; altrimenti vengono usate le viste di default incluse in questa libreria.
 */
class DefaultRouteController extends BaseWebController {

    #[Route('/', 'GET', summary: 'Home page', hidden: true)]
    public function index(): void {
        $this->renderTemplate('index');
    }

    #[Route('/api', 'GET', summary: 'Swagger UI', hidden: true)]
    public function apiIndex(): void {
        $this->renderTemplate('swagger');
    }

    #[Route('/api/openapi', 'GET', summary: 'OpenAPI spec generato', hidden: true)]
    public function openapi(): void {
        $apiControllers = ControllerScanner::scan($this->get('controllersPath'), $this->get('controllersNamespace'))['api'];
        $spec = OpenApiGenerator::generateFullSpec(
            $apiControllers,
            $this->get('config')['API_INFO'] ?? [],
            $this->request()->scheme.'://'.$this->request()->host.'/api'
        );

        $this->json($spec);
    }

    private function renderTemplate(string $name): void {
        $appViewFile = rtrim((string) $this->get('flight.views.path'), DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR . $name . '.html';
        if (file_exists($appViewFile)) {
            $this->render($name);
            return;
        }
        readfile(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $name . '.html');
    }

}
