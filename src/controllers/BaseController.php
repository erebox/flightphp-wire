<?php
declare(strict_types=1);
namespace core\controllers;

use flight\Engine;

abstract class BaseController {

    protected Engine $app;

    protected string $dirPath;
    protected string $webPath;
    protected string $dataPath;
    protected string $assetsPath;

    public function __construct(Engine $app) {
        $this->app = $app;

        $this->dirPath = $this->get('dirPath');
        $this->webPath = $this->get('webPath');
        $this->dataPath = $this->get('dataPath');
        $this->assetsPath = $this->dirPath.DIRECTORY_SEPARATOR."assets".DIRECTORY_SEPARATOR;
    }

    public function __call(string $name, array $arguments) {
        return $this->app->$name(...$arguments);
    }

}
