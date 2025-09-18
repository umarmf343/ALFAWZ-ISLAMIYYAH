<?php

namespace App\Console\Commands;

use cebe\openapi\Reader;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class OpenApiSyncCommand extends Command
{
    protected $signature = 'openapi:sync
        {--generate : Regenerate the OpenAPI document from registered routes}
        {--validate-only : Only validate the specification without generating clients}';

    protected $description = 'Validate the OpenAPI specification and regenerate TypeScript clients.';

    public function handle(): int
    {
        $specPath = base_path('docs/openapi.yaml');

        if ($this->option('generate')) {
            $this->info('Generating OpenAPI specification from routes...');
            $this->generateSpec($specPath);
        }

        $this->validateSpec($specPath);

        if ($this->option('validate-only')) {
            $this->info('Specification validation completed.');
            return self::SUCCESS;
        }

        $this->generateTypeScriptClient($specPath);

        $this->info('OpenAPI synchronisation completed successfully.');
        return self::SUCCESS;
    }

    private function generateSpec(string $specPath): void
    {
        $router = $this->laravel['router'];
        $paths = [];
        $tags = [];

        foreach ($router->getRoutes() as $route) {
            $uri = $route->uri();

            if (!Str::startsWith($uri, 'api')) {
                continue;
            }

            $relative = ltrim(Str::after($uri, 'api'), '/');
            $path = '/' . ltrim($relative, '/');
            if ($relative === '') {
                $path = '/';
            }

            $tag = $this->makeTagFromPath($path);
            $tags[$tag] = [
                'name' => $tag,
                'description' => "Endpoints under the " . strtolower($tag) . " namespace.",
            ];

            $middleware = $route->gatherMiddleware();
            $description = $this->describeAction($route);
            if ($note = $this->authorizationNote($middleware)) {
                $description .= ' ' . $note;
            }

            foreach ($route->methods() as $method) {
                $method = strtoupper($method);

                if (in_array($method, ['HEAD', 'OPTIONS'])) {
                    continue;
                }

                $operation = [
                    'tags' => [$tag],
                    'summary' => $this->makeSummary($route, $method, $path),
                    'description' => $description,
                    'operationId' => $this->makeOperationId($method, $path),
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/GenericResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];

                if ($this->requiresSecurity($middleware)) {
                    $operation['security'] = [['Sanctum' => []]];
                    $operation['responses']['401'] = [
                        '$ref' => '#/components/responses/Unauthenticated',
                    ];
                }

                if ($this->requiresAuthorization($middleware)) {
                    $operation['responses']['403'] = [
                        '$ref' => '#/components/responses/Forbidden',
                    ];
                }

                if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/GenericPayload',
                                ],
                            ],
                        ],
                    ];
                    $operation['responses']['422'] = [
                        '$ref' => '#/components/responses/ValidationError',
                    ];
                }

                $paths[$path][strtolower($method)] = $operation;
            }
        }

        ksort($paths);
        foreach ($paths as $path => $operations) {
            $paths[$path] = $this->sortOperations($operations);
        }

        ksort($tags);

        $document = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => "AlFawz Qur'an Institute API",
                'version' => '1.0.0',
                'description' => 'Automatically generated specification from Laravel routes.',
            ],
            'servers' => [
                ['url' => 'https://api.alfawz.local/api', 'description' => 'Production API base URL (example)'],
                ['url' => 'http://localhost:8000/api', 'description' => 'Local development API'],
            ],
            'tags' => array_values($tags),
            'paths' => $paths,
            'components' => $this->componentsDefinition(),
        ];

        $directory = dirname($specPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($specPath, Yaml::dump($document, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
    }

    private function makeTagFromPath(string $path): string
    {
        $segments = array_filter(explode('/', trim($path, '/')));
        $first = $segments[0] ?? 'general';

        return Str::headline($first ?: 'general');
    }

    private function sortOperations(array $operations): array
    {
        $order = ['get', 'post', 'put', 'patch', 'delete'];

        uksort($operations, function (string $a, string $b) use ($order) {
            $positionA = array_search($a, $order, true);
            $positionB = array_search($b, $order, true);

            $positionA = $positionA === false ? PHP_INT_MAX : $positionA;
            $positionB = $positionB === false ? PHP_INT_MAX : $positionB;

            if ($positionA === $positionB) {
                return strcmp($a, $b);
            }

            return $positionA <=> $positionB;
        });

        return $operations;
    }

    private function makeSummary(Route $route, string $method, string $path): string
    {
        $action = $route->getActionMethod();

        if ($action && $action !== '__invoke') {
            $map = [
                'index' => 'List resources',
                'store' => 'Create resource',
                'show' => 'Get resource',
                'update' => 'Update resource',
                'destroy' => 'Delete resource',
            ];

            if (isset($map[$action])) {
                return $map[$action];
            }

            return Str::headline($action);
        }

        $pathSummary = trim($path, '/') ?: 'root';
        return Str::headline(strtolower($method) . ' ' . str_replace('/', ' ', $pathSummary));
    }

    private function describeAction(Route $route): string
    {
        $action = $route->getActionName();

        if ($action === 'Closure') {
            return 'Handled by inline closure.';
        }

        if (str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            return sprintf('Handled by %s::%s.', class_basename($class), $method);
        }

        return sprintf('Handled by %s.', $action);
    }

    private function authorizationNote(array $middleware): ?string
    {
        $notes = [];

        foreach ($middleware as $item) {
            if (str_starts_with($item, 'can:')) {
                $notes[] = 'Requires ability `' . Str::after($item, 'can:') . '`.';
            }

            if (str_starts_with($item, 'role:')) {
                $notes[] = 'Requires role `' . Str::after($item, 'role:') . '`.';
            }
        }

        return empty($notes) ? null : implode(' ', $notes);
    }

    private function requiresSecurity(array $middleware): bool
    {
        return in_array('auth:sanctum', $middleware, true);
    }

    private function requiresAuthorization(array $middleware): bool
    {
        foreach ($middleware as $item) {
            if (str_starts_with($item, 'can:') || str_starts_with($item, 'role:')) {
                return true;
            }
        }

        return false;
    }

    private function makeOperationId(string $method, string $path): string
    {
        $identifier = strtolower($method) . ' ' . str_replace(['/', '{', '}', '-'], ' ', trim($path, '/'));

        if ($identifier === '') {
            $identifier = strtolower($method) . ' root';
        }

        return Str::camel($identifier);
    }

    private function componentsDefinition(): array
    {
        return [
            'securitySchemes' => [
                'Sanctum' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Token',
                    'description' => 'Laravel Sanctum personal access token.',
                ],
            ],
            'schemas' => [
                'GenericPayload' => [
                    'type' => 'object',
                    'description' => 'Generic JSON payload accepted by write operations.',
                    'additionalProperties' => true,
                ],
                'GenericResponse' => [
                    'type' => 'object',
                    'description' => 'Standard JSON envelope returned by the API.',
                    'properties' => [
                        'success' => ['type' => 'boolean', 'default' => true],
                        'message' => ['type' => 'string', 'nullable' => true],
                        'data' => ['type' => 'object', 'nullable' => true, 'additionalProperties' => true],
                        'errors' => ['type' => 'object', 'nullable' => true, 'additionalProperties' => true],
                    ],
                    'additionalProperties' => true,
                ],
                'ErrorResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => ['type' => 'string'],
                        'errors' => ['type' => 'object', 'nullable' => true, 'additionalProperties' => true],
                    ],
                ],
                'ValidationErrors' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
            ],
            'responses' => [
                'Unauthenticated' => [
                    'description' => 'Authentication required.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ErrorResponse',
                            ],
                        ],
                    ],
                ],
                'Forbidden' => [
                    'description' => 'The user is not allowed to access this resource.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ErrorResponse',
                            ],
                        ],
                    ],
                ],
                'ValidationError' => [
                    'description' => 'Validation failed for the supplied payload.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string'],
                                    'errors' => [
                                        '$ref' => '#/components/schemas/ValidationErrors',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function validateSpec(string $specPath): void
    {
        if (!file_exists($specPath)) {
            throw new RuntimeException('OpenAPI specification not found. Run with --generate first.');
        }

        $document = Reader::readFromYamlFile($specPath);

        if (!$document) {
            throw new RuntimeException('Unable to parse OpenAPI document.');
        }

        $result = $document->validate();

        if ($result !== true) {
            if (!is_array($result)) {
                throw new RuntimeException('OpenAPI document validation failed.');
            }

            foreach ($result as $message) {
                $this->error($message);
            }

            throw new RuntimeException('OpenAPI document validation failed.');
        }

        $this->info('OpenAPI specification validated successfully.');
    }

    private function generateTypeScriptClient(string $specPath): void
    {
        $webPath = realpath(base_path('../web'));

        if (!$webPath) {
            throw new RuntimeException('Unable to locate web application path for client generation.');
        }

        $outputDir = $webPath . '/src/lib/generated';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $command = 'npx openapi-typescript ../api/docs/openapi.yaml --output src/lib/generated/client.ts';
        $process = Process::fromShellCommandline($command, $webPath, null, null, null);

        $process->run(function (string $type, string $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Failed to generate TypeScript client.');
        }

        $this->info('TypeScript client generated in apps/web/src/lib/generated.');
    }
}
