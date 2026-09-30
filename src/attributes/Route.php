<?php
declare(strict_types=1);
namespace core\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    /**
     * @param string $path Path della route, sintassi FlightPHP (es. '/user/@id')
     * @param string $method Verbo HTTP ('GET', 'POST', 'GET|POST', ecc.)
     * @param string[] $middlewares Classi middleware da applicare
     * @param string $summary Breve titolo (per OpenAPI)
     * @param string $description Descrizione estesa (per OpenAPI)
     * @param string[] $tags Tag di raggruppamento (per OpenAPI)
     * @param bool $hidden Escludi dalla documentazione OpenAPI
     * @param string $schemaName Nome dello schema di risposta (es. 'Pong')
     * @param array $schemaProperties Proprietà dello schema, es. ['ping' => 'string']
     * @param array $queryParams Parametri accettati in query string, es. ['testo' => 'string']
     *   oppure con default, es. ['testo' => ['type' => 'string', 'default' => 'foo']]
     */
    public function __construct(
        public string $path,
        public string $method = 'GET',
        public array $middlewares = [],
        public string $summary = '',
        public string $description = '',
        public array $tags = [],
        public bool $hidden = false,
        public string $schemaName = '',
        public array $schemaProperties = [],
        public array $queryParams = []
    ) {}
}
