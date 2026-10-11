<?php
declare(strict_types=1);
namespace core\controllers;

use flight\Engine;

abstract class BaseController {

    protected Engine $app;

    protected string $dirPath;
    /** Percorso pubblico del sito senza '/' finale ('' alla radice del dominio, '/sottocartella' altrimenti) */
    protected string $basePath;
    /** Percorso pubblico della radice del sito (basePath + '/') */
    protected string $webPath;
    protected string $dataPath;
    protected string $assetsPath;
    protected string $filesPath;

    public function __construct(Engine $app) {
        $this->app = $app;

        $this->dirPath = $this->get('dirPath');
        $this->basePath = (string) $this->get('basePath');
        $this->webPath = $this->get('webPath');
        $this->dataPath = $this->get('dataPath');
        
        $this->assetsPath = $this->dirPath.DIRECTORY_SEPARATOR."assets".DIRECTORY_SEPARATOR;
        $this->filesPath = $this->dirPath.DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR;
    }

    /**
     * Indirizzo pubblico di un percorso del sito, con il percorso base davanti (es. webUrl('/upload') → '/sottocartella/upload').
     * Da usare per link e redirect verso le pagine del sito.
     */
    protected function webUrl(string $path = '/'): string {
        return $this->basePath.'/'.ltrim($path, '/');
    }

    public function __call(string $name, array $arguments) {
        return $this->app->$name(...$arguments);
    }

}
