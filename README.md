# flightphp-wire

Convenzioni di progetto condivise fra i siti basati su [FlightPHP](https://flightphp.com/):

- `core\Bootstrap` inizializza l'engine Flight (path dei controller, config da `data/config.json`, censimento controller, registrazione route).
- `core\controllers\BaseController` / `BaseWebController` / `BaseApiController` sono le classi base da cui estendere i controller del sito; il tipo di classe base determina la classificazione automatica (web vs API).
- `core\attributes\Route` è l'attributo PHP con cui annotare i metodi dei controller per dichiarare path, verbo HTTP, middleware e metadati OpenAPI.
- `core\helpers\ControllerScanner`, `AttributeRouteRegistrar` e `OpenApiGenerator` gestiscono rispettivamente il censimento dei controller, la registrazione delle route e la generazione dello spec OpenAPI.
- `core\middlewares\WebHeader` e `ValidateApikey` sono i middleware di default applicati rispettivamente alle route web e API.

## Installazione

```bash
composer require erebox/flightphp-wire
```

## Uso

Nel file di ingresso del sito (es. `index.php`):

```php
require 'vendor/autoload.php';
use core\Bootstrap;

$app = Bootstrap::init(__DIR__.'/app', 'app\\controllers');
$app->start();
```

`Bootstrap::init` si aspetta la convenzione comune ai siti: controller in `app/controllers` (che estendono `BaseWebController`/`BaseApiController`), route dichiarate con l'attributo `Route`, config in `data/config.json`.
