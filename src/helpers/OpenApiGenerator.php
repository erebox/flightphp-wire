<?php
declare(strict_types=1);
namespace core\helpers;

use core\attributes\Route;
use ReflectionClass;
use ReflectionMethod;

class OpenApiGenerator {

    /**
     * Genera lo spec OpenAPI completo dai controller API + metadati da config.
     *
     * @param class-string[] $controllerClasses
     * @param array $apiInfo Sezione API_INFO del config.json (title, description, contactEmail, version, securitySchemes, defaultSecurity)
     * @param string $serverUrl URL completo del server API
     */
    public static function generateFullSpec(array $controllerClasses, array $apiInfo, string $serverUrl, string $stripPrefix = '/api'): array {
        $defaultSecurity = $apiInfo['defaultSecurity'] ?? [];
        $securityEntry = array_map(fn($name) => [$name => []], $defaultSecurity);

        ['paths' => $paths, 'schemas' => $schemas] = self::generate($controllerClasses, $stripPrefix, $securityEntry);

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $apiInfo['title'] ?? 'API',
                'description' => $apiInfo['description'] ?? '',
                'contact' => ['email' => $apiInfo['contactEmail'] ?? ''],
                'version' => $apiInfo['version'] ?? '1.0.0',
            ],
            'servers' => [
                ['url' => $serverUrl],
            ],
            'paths' => $paths,
            'components' => [
                'schemas' => $schemas,
                'securitySchemes' => $apiInfo['securitySchemes'] ?? [],
            ],
        ];
    }

    /**
     * @param class-string[] $controllerClasses
     * @return array{paths: array, schemas: array}
     */
    public static function generate(array $controllerClasses, string $stripPrefix = '/api', array $securityEntry = []): array {
        $paths = [];
        $schemas = [];

        foreach ($controllerClasses as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(Route::class) as $attribute) {
                    /** @var Route $route */
                    $route = $attribute->newInstance();

                    if ($route->hidden) {
                        continue;
                    }

                    $relativePath = self::stripPrefix($route->path, $stripPrefix);
                    $openApiPath = self::toOpenApiPath($relativePath);
                    $parameters = array_merge(
                        self::extractPathParams($relativePath),
                        self::buildQueryParams($route->queryParams)
                    );

                    $responseSchema = null;
                    if ($route->schemaName !== '') {
                        $schemas[$route->schemaName] = self::buildSchema($route->schemaProperties);
                        $responseSchema = ['$ref' => '#/components/schemas/' . $route->schemaName];
                    }

                    foreach (explode('|', $route->method) as $verb) {
                        $verb = strtolower($verb);

                        $responses200 = ['description' => 'Successo'];
                        if ($responseSchema) {
                            $responses200['content'] = [
                                'application/json' => ['schema' => $responseSchema],
                            ];
                        }

                        $entry = [
                            'summary' => $route->summary ?: $method->getName(),
                        ];

                        if ($route->description !== '') {
                            $entry['description'] = $route->description;
                        }

                        $entry['tags'] = $route->tags ?: [$reflection->getShortName()];
                        $entry['responses'] = ['200' => $responses200];

                        if ($securityEntry) {
                            $entry['security'] = $securityEntry;
                        }

                        if ($parameters) {
                            $entry['parameters'] = $parameters;
                        }

                        $paths[$openApiPath][$verb] = $entry;
                    }
                }
            }
        }

        return ['paths' => $paths, 'schemas' => $schemas];
    }

    private static function buildSchema(array $properties): array {
        $props = [];
        foreach ($properties as $name => $type) {
            $props[$name] = ['type' => $type];
        }

        return ['type' => 'object', 'properties' => $props];
    }

    private static function stripPrefix(string $path, string $prefix): string {
        if ($prefix !== '' && str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }
        return $path === '' ? '/' : $path;
    }

    private static function toOpenApiPath(string $flightPath): string {
        $path = preg_replace('/\(\/@(\w+)\)/', '/{$1}', $flightPath);
        $path = preg_replace('/@(\w+)/', '{$1}', $path);
        return $path;
    }

    private static function buildQueryParams(array $queryParams): array {
        return array_map(function ($name, $spec) {
            $type = is_array($spec) ? ($spec['type'] ?? 'string') : $spec;
            $schema = ['type' => $type];
            if (is_array($spec) && array_key_exists('default', $spec)) {
                $schema['default'] = $spec['default'];
            }
            return [
                'name' => $name,
                'in' => 'query',
                'required' => false,
                'schema' => $schema,
            ];
        }, array_keys($queryParams), array_values($queryParams));
    }

    private static function extractPathParams(string $flightPath): array {
        preg_match_all('/@(\w+)/', $flightPath, $matches);
        return array_map(fn($name) => [
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'string'],
        ], $matches[1]);
    }

}
