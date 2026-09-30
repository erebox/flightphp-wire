<?php
declare(strict_types=1);
namespace core;

use Flight;
use flight\Engine;

use core\helpers\Utils;

use core\helpers\AttributeRouteRegistrar;
use core\helpers\ControllerScanner;

use core\middlewares\WebHeader;
use core\middlewares\ValidateApikey;

class Bootstrap {

    /**
     * Inizializza l'engine Flight con la convenzione di progetto comune a tutti i siti:
     * controller in app/controllers auto-censiti tramite BaseWebController/BaseApiController,
     * route dichiarate con l'attributo Route, config letta da data/config.json.
     *
     * @param string $appDir Cartella 'app' del sito (contiene controllers/, views/, ...)
     * @param string $controllersNamespace Namespace PSR-4 dei controller del sito (es. 'app\\controllers')
     * @param string $configFile Nome del file di config dentro la cartella 'data' del sito
     */
    public static function init(string $appDir, string $controllersNamespace, string $configFile = 'config.json'): Engine {
        $app = Flight::app();

        $app->register('utils', Utils::class);

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
        $app->set('webPath', '/');
        $dirDataPath = $dirRootPath.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
        $app->set('dataPath', $dirDataPath);
        $app->set('controllersPath', $dirBasePath.'controllers'.DIRECTORY_SEPARATOR);

        $app->set('dir', $dirRootPath);
        $app->set('flight.views.path', $dirBasePath.'views');
        $app->set('flight.views.extension', '.html');

        $app->set('config', Utils::readJson($dirDataPath, $configFile));

        $controllers = ControllerScanner::scan($app->get('controllersPath'), $controllersNamespace);
        AttributeRouteRegistrar::registerMany($app, $controllers['web'], [WebHeader::class]);
        AttributeRouteRegistrar::registerMany($app, $controllers['api'], [ValidateApikey::class], '/api');

        return $app;
    }

}
