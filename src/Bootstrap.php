<?php
declare(strict_types=1);
namespace core;

use Flight;
use flight\Engine;

use core\helpers\Utils;
use core\helpers\Session;
use core\helpers\ErrorPages;

use core\helpers\AttributeRouteRegistrar;
use core\helpers\ControllerScanner;

use core\controllers\DefaultRouteController;

use core\middlewares\WebHeader;
use core\middlewares\ValidateApikey;

class Bootstrap {

    /**
     * Inizializza l'engine Flight con la convenzione di progetto comune a tutti i siti:
     * controller in app/controllers auto-censiti tramite BaseWebController/BaseApiController,
     * route dichiarate con l'attributo Route, config letta da data/config.json (sopra i default di src/config/default.json).
     *
     * @param string $appDir Cartella 'app' del sito (contiene controllers/, views/, ...)
     * @param string $controllersNamespace Namespace PSR-4 dei controller del sito (es. 'app\\controllers')
     * @param string $configFile Nome del file di config dentro la cartella 'data' del sito
     */
    public static function init(string $appDir, string $controllersNamespace, string $configFile = 'config.json'): Engine {
        $app = Flight::app();

        $app->register('utils', Utils::class);
        $app->register('session', Session::class);

        $dirCorePath = __DIR__.DIRECTORY_SEPARATOR;
        $app->path($dirCorePath);
        $app->path($dirCorePath.'controllers'.DIRECTORY_SEPARATOR);
        $app->path($dirCorePath.'helpers'.DIRECTORY_SEPARATOR);
        $app->path($dirCorePath.'middlewares'.DIRECTORY_SEPARATOR);
        $app->path($dirCorePath.'attributes'.DIRECTORY_SEPARATOR);

        $dirBasePath = rtrim($appDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $app->path($dirBasePath);
        $app->path($dirBasePath.'controllers'.DIRECTORY_SEPARATOR);

        $dirRootPath = dirname($dirBasePath);
        $app->set('dirPath', $dirRootPath);
        $dirDataPath = $dirRootPath.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
        $app->set('dataPath', $dirDataPath);
        $app->set('controllersPath', $dirBasePath.'controllers'.DIRECTORY_SEPARATOR);
        $app->set('controllersNamespace', $controllersNamespace);

        $app->set('dir', $dirRootPath);
        $app->set('flight.views.path', $dirBasePath.'views');
        $app->set('flight.views.extension', '.html');

        // Config del sito sovrapposta (merge ricorsivo) a quella di default della libreria (src/config/default.json)
        $app->set('config', array_replace_recursive(
            Utils::readJson($dirCorePath.'config'.DIRECTORY_SEPARATOR, 'default.json'),
            Utils::readJson($dirDataPath, $configFile)
        ));

        // Percorso base del sito: la sottocartella in cui si trova index.php ('' se il sito è alla radice del dominio).
        // basePath ('' o '/sottocartella') e webPath (basePath + '/') sono gli indirizzi pubblici da usare per link e redirect:
        // i redirect ricevono quindi già il percorso completo e Flight non deve aggiungere di suo la sottocartella.
        $basePath = self::basePath($app);
        $app->set('basePath', $basePath);
        $app->set('webPath', $basePath.'/');
        $app->set('flight.base_url', '/');
        $app->view()->set('basePath', htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'));

        $controllers = ControllerScanner::scan($app->get('controllersPath'), $controllersNamespace);

        AttributeRouteRegistrar::registerMany($app, $controllers['web'], [WebHeader::class]);
        AttributeRouteRegistrar::registerMany($app, $controllers['api'], [ValidateApikey::class], '/api');

        // Route di default della libreria (homepage, Swagger UI, /api/openapi): vengono registrate solo per i path che il sito non ha già definito autonomamente,
        // indipendentemente dal fatto che il sito abbia una propria classe DefaultRouteController.
        $siteRoutes = array_merge(
            AttributeRouteRegistrar::collectRoutes($controllers['web']),
            AttributeRouteRegistrar::collectRoutes($controllers['api'], '/api')
        );
        AttributeRouteRegistrar::register($app, DefaultRouteController::class, [WebHeader::class], '', $siteRoutes);

        // Pagine di errore 404/405/500 (JSON sotto /api, vista 'error.html' altrove)
        ErrorPages::register($app, '/api');

        return $app;
    }

    /**
     * Percorso base senza '/' finale: dalla chiave BASEPATH della config se impostata (es. sito dietro un proxy),
     * altrimenti dalla cartella dello script (index.php) ricavata da Flight.
     */
    private static function basePath(Engine $app): string {
        $configurato = $app->get('config')['BASEPATH'] ?? null;
        $base = is_string($configurato) && trim($configurato, '/ ') !== ''
            ? $configurato
            : (string) $app->request()->base;
        $base = trim(str_replace('\\', '/', $base), '/ ');
        return $base === '' || $base === '.' ? '' : '/'.$base;
    }

}
