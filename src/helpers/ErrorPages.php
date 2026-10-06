<?php
declare(strict_types=1);
namespace core\helpers;

use Throwable;
use flight\Engine;
use flight\net\Route;

/**
 * Pagine di errore: sostituisce le risposte di default di Flight
 * per 404 (route inesistente), 405 (metodo non ammesso) e 500 (eccezione non gestita).
 * Sotto il prefisso delle API risponde in JSON, altrove renderizza la vista 'error.html'
 * del sito se presente in app/views, altrimenti quella di default della libreria.
 */
class ErrorPages {

    private const TITLES = [
        404 => 'Pagina non trovata',
        405 => 'Metodo non consentito',
        500 => 'Errore interno',
    ];

    /**
     * @param string $apiPrefix Prefisso delle route API, per cui gli errori sono restituiti in JSON
     */
    public static function register(Engine $app, string $apiPrefix = '/api'): void {
        $app->map('notFound', function () use ($app, $apiPrefix): void {
            self::send($app, $apiPrefix, 404, 'La pagina richiesta non esiste o è stata spostata.');
        });

        $app->map('methodNotFound', function (Route $route) use ($app, $apiPrefix): void {
            $app->response()->setHeader('Allow', implode(', ', $route->methods));
            self::send($app, $apiPrefix, 405, 'Metodi consentiti: '.implode(', ', $route->methods).'.');
        });

        $app->map('error', function (Throwable $e) use ($app, $apiPrefix): void {
            error_log($e->getMessage()."\n".$e->getTraceAsString());
            $detail = $app->get('flight.debug') === true ? get_class($e).': '.$e->getMessage() : '';
            self::send($app, $apiPrefix, 500, 'Si è verificato un errore imprevisto. Riprova più tardi.', $detail);
        });
    }

    private static function send(Engine $app, string $apiPrefix, int $code, string $message, string $detail = ''): void {
        $title = self::TITLES[$code] ?? 'Errore';
        $path = parse_url($app->request()->url, PHP_URL_PATH) ?: '/';

        if ($path === $apiPrefix || str_starts_with($path, $apiPrefix.'/')) {
            $body = (string) json_encode(['error' => $title.'.']);
            $type = 'application/json; charset=utf-8';
        } else {
            try {
                $body = $app->view()->fetch(self::view($app), compact('code', 'title', 'message', 'detail', 'path'));
            } catch (Throwable $t) {
                // la vista stessa è rotta: ripiega su un HTML minimo
                $body = '<h1>'.$code.' '.htmlspecialchars($title).'</h1>';
            }
            $type = 'text/html; charset=utf-8';
        }

        $app->response()
            ->clearBody()
            ->cache(0)
            ->status($code)
            ->header('Content-Type', $type)
            ->write($body)
            ->send();
    }

    private static function view(Engine $app): string {
        $name = 'error';
        $appViewFile = rtrim((string) $app->get('flight.views.path'), DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR . $name . '.html';
        return file_exists($appViewFile) ? $name : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $name . '.html';
    }

}
