# flightphp-wire

<img src="flightphp-wire.svg" alt="flightphp-wire" width="200">

Convenzioni di progetto condivise fra i siti basati su [FlightPHP](https://flightphp.com/):

- `core\Bootstrap` inizializza l'engine Flight (path dei controller, config da `data/config.json`, censimento controller, registrazione route).
- `core\controllers\BaseController` / `BaseWebController` / `BaseApiController` sono le classi base da cui estendere i controller del sito; il tipo di classe base determina la classificazione automatica (web vs API).
- `core\attributes\Route` è l'attributo PHP con cui annotare i metodi dei controller per dichiarare path, verbo HTTP, middleware e metadati OpenAPI.
- `core\helpers\ControllerScanner`, `AttributeRouteRegistrar` e `OpenApiGenerator` gestiscono rispettivamente il censimento dei controller, la registrazione delle route e la generazione dello spec OpenAPI.
- `core\helpers\Session` è registrata come servizio Flight `session` (istanza unica e lazy: `$this->session()` nei controller). Legge la sessione senza tenerne il lock (`read_and_close`), non la avvia se il browser non ha il cookie e scrive solo con `commit()`; `regenerate()` rigenera l'id al commit (da usare dopo il login). Opzioni di default: cookie `SESS_ID`, `HttpOnly`, `SameSite=Lax`, `Secure` se in HTTPS.
- `core\middlewares\WebHeader` e `ValidateApikey` sono i middleware di default applicati rispettivamente alle route web e API.
- `BaseWebController::renderPageLinks($title, $controllerClass = null)` renderizza una pagina con i link alle pagine del controller (di default quello corrente); `pageLinks($controllerClass)` restituisce lo stesso elenco come array. Sono incluse solo le route `GET` senza parametri di path e non marcate `hidden`; l'etichetta è il `summary` della route (o il path). La vista `links.html` è quella del sito se presente in `app/views`, altrimenti quella di default della libreria.
- `core\controllers\DefaultRouteController` fornisce le route di default: `/` (homepage), `/api` (Swagger UI) e `/api/openapi` (spec generata). `Bootstrap::init` le registra sempre, ma salta quelle il cui path+metodo è già dichiarato da un controller del sito: un sito può quindi sovrascrivere una singola route (es. solo `/`) senza perdere le altre. Le viste `index.html`/`swagger.html` sono quelle del sito se presenti in `app/views`, altrimenti vengono usate quelle di default incluse nella libreria (`src/views`).

## Installazione

```bash
composer require erebox/flightphp-wire
```

## Uso

Nel file iniziale del sito (es. `index.php`):

```php
require 'vendor/autoload.php';
use core\Bootstrap;

$app = Bootstrap::init(__DIR__.'/app', 'app\\controllers');
$app->start();
```

`Bootstrap::init` si aspetta la convenzione comune ai siti: controller in `app/controllers` (che estendono `BaseWebController`/`BaseApiController`), route dichiarate con l'attributo `Route`, config in `data/config.json`.
