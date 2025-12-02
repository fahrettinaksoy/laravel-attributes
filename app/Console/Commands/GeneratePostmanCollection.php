<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GeneratePostmanCollection extends Command
{
    protected $signature = 'postman:generate {--name=} {--collection-version=1.0.0} {--auth-token=} {--auth=true}';
    protected $description = 'Generate Postman collection from dynamic Model structure and Laravel routes';
    protected array $collection = [];
    protected ?string $authToken = null;
    protected bool $authEnabled = true;
    protected array $excludedDirectories = ['Base', 'Shared', 'Common', 'Traits', 'Concerns', 'Contracts', 'Abstracts', 'DTO', 'ValueObjects'];
    protected array $excludedModelNames = ['BaseModel', 'AbstractModel', 'Base'];
    protected array $excludedModelNamePatterns = ['/^Base[A-Za-z0-9]*Model$/', '/^Abstract.+Model$/', '/^Base$/'];
    protected array $excludedRouteFirstSegments = ['base'];

    public function handle(): int
    {
        if (!$this->option('name')) $this->input->setOption('name', basename(base_path()));

        // Parse auth option (handles both boolean and string values)
        $authOption = $this->option('auth');
        $this->authEnabled = filter_var($authOption, FILTER_VALIDATE_BOOLEAN);

        $this->initCollection();
        $this->buildEndpoints();
        $this->writeCollectionFile();
        $this->writeEnvironments();
        return Command::SUCCESS;
    }

    protected function initCollection(): void
    {
        $this->authToken = $this->option('auth-token');
        $this->collection = [
            'info' => [
                'name' => $this->option('name'),
                'description' => $this->collectionDoc(),
                'version' => $this->option('collection-version'),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        if ($this->authEnabled) {
            $this->collection['variable'] = [
                ['key' => 'authToken', 'value' => $this->authToken ?: 'your_bearer_token_here', 'type' => 'string']
            ];
        }
    }

    protected function buildEndpoints(): void
    {
        $tree = $this->scanModels(app_path('Models'));
        $modelItems = $this->toPostmanTree($tree);
        $routeItems = $this->routesToPostman();
        $merged = $this->pruneExcluded($this->mergeFolders($modelItems, $routeItems));

        $coreFolder = [
            'name' => 'Core',
            'item' => $merged,
            'description' => 'All API endpoints (Models + Routes)',
        ];

        if ($this->authEnabled) {
            $coreFolder['auth'] = [
                'type' => 'bearer',
                'bearer' => [
                    ['key' => 'token', 'value' => '{{authToken}}', 'type' => 'string']
                ]
            ];
            $coreFolder['event'] = [
                [
                    'listen' => 'prerequest',
                    'script' => [
                        'exec' => [
                            'const protocol = pm.environment.get("protocol");',
                            'const subdomain = pm.environment.get("subDomain");',
                            'const domain = pm.environment.get("domain");',
                            'const path = pm.environment.get("path");',
                            'const version = pm.environment.get("version");',
                            '',
                            'const baseUrl = `${protocol}${subdomain}${domain}${path}${version}`;',
                            '',
                            'if (!pm.environment.get("authToken")) {',
                            '  pm.sendRequest({',
                            '    url: `${baseUrl}/auth/login`,',
                            '    method: "POST",',
                            '    header: { "Content-Type": "application/json" },',
                            '    body: {',
                            '      mode: "raw",',
                            '      raw: JSON.stringify({',
                            '        email: pm.environment.get("authEmail"),',
                            '        password: pm.environment.get("authPassword"),',
                            '        device_name: "postman"',
                            '      })',
                            '    }',
                            '  }, (err, res) => {',
                            '    if (err) { console.error("Token isteğinde hata:", err); return; }',
                            '    let json;',
                            '    try { json = res.json(); } catch (e) { console.error("Geçersiz JSON:", res.text()); return; }',
                            '    if (json.token) {',
                            '      pm.environment.set("authToken", json.token);',
                            '      postman.setNextRequest(pm.info.requestName);',
                            '    } else { console.error("Token alınamadı:", json); }',
                            '  });',
                            '}'
                        ],
                        'type' => 'text/javascript'
                    ]
                ]
            ];
        }

        $authFolder = [
            'name' => 'Auth',
            'description' => 'Authentication endpoints',
            'item' => [
                ['name' => 'Login', 'request' => ['method' => 'POST', 'header' => $this->headers('POST'), 'url' => ['raw' => '{{apiURL}}{{version}}/auth/login', 'host' => ['{{apiURL}}{{version}}'], 'path' => ['auth', 'login']], 'body' => ['mode' => 'raw', 'raw' => json_encode(['email' => '{{authEmail}}', 'password' => '{{authPassword}}', 'device_name' => 'postman'])], 'description' => 'User login']],
                ['name' => 'Register', 'request' => ['method' => 'POST', 'header' => $this->headers('POST'), 'url' => ['raw' => '{{apiURL}}{{version}}/auth/register', 'host' => ['{{apiURL}}{{version}}'], 'path' => ['auth', 'register']], 'body' => ['mode' => 'raw', 'raw' => json_encode(['first_name' => '{{authNameFirst}}', 'last_name' => '{{authNameLast}}', 'email' => '{{authEmail}}', 'password' => '{{authPassword}}', 'password_confirmation' => '{{authPassword}}'])], 'description' => 'User registration']],
            ],
        ];

        $this->collection['item'] = $this->authEnabled ? [$coreFolder, $authFolder] : [$coreFolder];
    }

    protected function writeCollectionFile(): void
    {
        $projectName = $this->option('name');
        $fileName = "{$projectName}-collection.json";
        $json = json_encode($this->collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($this->postmanPath($fileName), $json);
        $this->info('📊 Collection stats:');
        $this->info("   • File: postman/{$fileName}");
        $this->info('   • Total requests: ' . $this->countRequests($this->collection['item']));
        $this->info('   • File size: ' . $this->formatBytes(strlen($json)));
        $this->info('   • Auth enabled: ' . ($this->authEnabled ? 'Yes' : 'No'));
    }

    protected function writeEnvironments(): void
    {
        $projectName = $this->option('name');
        $envs = ['local' => "{$projectName}-environment-local.json", 'stage' => "{$projectName}-environment-stage.json", 'master' => "{$projectName}-environment-master.json"];
        foreach ($envs as $env => $fileName) {
            File::put($this->postmanPath($fileName), json_encode($this->buildEnvironment($env, $projectName), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("🌍 {$env} environment file created: postman/{$fileName}");
        }
    }

    protected function routesToPostman(): array
    {
        $items = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/v1') || str_contains($uri, '{path}')) continue;

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $cleanPath = str_replace('api/v1/', '', $uri);
                $firstSeg = strtolower(explode('/', $cleanPath)[0] ?? '');
                $configuredSegs = array_map('strtolower', (array)config('postman_generator.excluded_route_first_segments', []));
                if (in_array($firstSeg, array_unique(array_merge($this->excludedRouteFirstSegments, $configuredSegs)), true)) continue;
                if (in_array($cleanPath, ['auth/login', 'auth/register'], true)) continue;

                $routeName = $route->getName();
                $pathParts = $routeName ? explode('.', $routeName) : explode('/', $cleanPath);
                if ($cleanPath === 'auth/logout' || $cleanPath === 'auth/user') $pathParts = ['Auth', end($pathParts)];

                $endpoint = [
                    'name' => strtoupper($method) . ' ' . ucfirst(end($pathParts)),
                    'request' => [
                        'method' => $method,
                        'header' => $this->headers($method),
                        'url' => ['raw' => '{{apiURL}}{{version}}/' . $cleanPath, 'host' => ['{{apiURL}}{{version}}'], 'path' => explode('/', $cleanPath)],
                        'description' => 'Auto-generated from ' . ($routeName ?: 'URI'),
                    ],
                ];
                $this->pushIntoTree($items, $pathParts, $endpoint);
            }
        }
        return $items;
    }

    protected function pushIntoTree(array &$items, array $pathParts, array $endpoint): void
    {
        $current = &$items;
        foreach ($pathParts as $i => $part) {
            $normalized = ucfirst(strtolower($part));
            if ($i === count($pathParts) - 1) {
                $endpoint['name'] = strtoupper($endpoint['request']['method']) . ' ' . $normalized;
                $current[] = $endpoint;
                return;
            }

            $folderIndex = null;
            foreach ($current as $idx => &$child) {
                if (isset($child['name'], $child['item']) && strtolower($child['name']) === strtolower($normalized) && is_array($child['item'])) {
                    $folderIndex = $idx;
                    break;
                }
            }

            if ($folderIndex !== null) {
                $current = &$current[$folderIndex]['item'];
            } else {
                $current[] = ['name' => $normalized, 'item' => []];
                $current = &$current[array_key_last($current)]['item'];
            }
        }
    }

    protected function scanModels(string $path, string $rel = ''): array
    {
        if (!File::isDirectory($path)) return [];
        $structure = [];
        foreach (File::glob($path . '/*') as $item) {
            $name = basename($item);
            $normalized = ucfirst($name);

            if (File::isDirectory($item)) {
                if ($this->isExcludedDir($name) || $this->isExcludedDir($normalized)) continue;

                if (strtolower($name) === 'relation' || strtolower($name) === 'relations') {
                    $sub = $this->scanModels($item, $rel . '/Relation');
                    if (!empty($sub)) $structure['Relation'] = ['type' => 'relation', 'parent' => $this->parentModule($rel), 'children' => $sub];
                } else {
                    $sub = $this->scanModels($item, $rel . '/' . $normalized);
                    if (!empty($sub)) $structure[$normalized] = ['type' => 'directory', 'children' => $sub];
                }
                continue;
            }

            if (!Str::endsWith($name, 'Model.php')) continue;
            $modelBase = basename($name, 'Model.php');
            if ($this->isExcludedModelBase($modelBase) || $this->isExcludedModelBase(basename($name, '.php'))) continue;

            $isRelationContext = str_contains($rel, '/Relation');
            if ($isRelationContext) {
                $parentModule = $this->parentModule($rel);
                $relationModelFqn = $this->fqnFromPath($item, 'Model');
                $parentModelFqn = $this->parentModelFqn($rel);
                $relationSlug = $this->guessRelationSlug($parentModelFqn, $relationModelFqn) ?: $this->relationFromModelName($modelBase);
                $structure['__CRUD__'] = [
                    'type' => 'relation_model',
                    'parent' => $parentModule,
                    'route' => $this->buildRelationRoute($rel, $relationSlug, $parentModule),
                    'file_path' => $item,
                    'relation_model_name' => $modelBase
                ];
            } else {
                if (strtolower($modelBase) === strtolower(basename(dirname($item)))) {
                    $structure['__CRUD__'] = ['type' => 'model', 'route' => $this->routeFromRel($rel, $modelBase, false), 'file_path' => $item];
                } else {
                    $structure[$modelBase] = ['type' => 'model', 'route' => $this->routeFromRel($rel, $modelBase, true), 'file_path' => $item];
                }
            }
        }
        return $structure;
    }

    protected function parentModule(string $rel): string
    {
        $parts = array_values(array_filter(explode('/', trim($rel, '/'))));

        $relationIdx = false;
        foreach ($parts as $idx => $part) {
            if (strtolower($part) === 'relation' || strtolower($part) === 'relations') {
                $relationIdx = $idx;
                break;
            }
        }

        return ($relationIdx !== false && $relationIdx > 0) ? $parts[$relationIdx - 1] : (end($parts) ?: '');
    }

    protected function parentModelFqn(string $rel): ?string
    {
        $parts = array_values(array_filter(explode('/', trim($rel, '/'))));
        $relationIdx = array_search('Relation', $parts, true);
        if ($relationIdx === false || $relationIdx === 0) return null;
        $parentParts = array_slice($parts, 0, $relationIdx);
        $fqn = 'App\\Models\\' . implode('\\', $parentParts) . '\\' . end($parentParts) . 'Model';
        return class_exists($fqn) ? $fqn : null;
    }

    protected function guessRelationSlug(?string $parentModelFqn, string $relationModelFqn): ?string
    {
        if (!$parentModelFqn || !class_exists($parentModelFqn) || !class_exists($relationModelFqn)) return null;
        try {
            if (!is_subclass_of($parentModelFqn, \Illuminate\Database\Eloquent\Model::class)) return null;
            $parent = new $parentModelFqn;
            foreach ((new \ReflectionClass($parentModelFqn))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0 || $method->isConstructor() || $method->isDestructor()) continue;
                try {
                    $rel = $method->invoke($parent);
                    if ($rel instanceof \Illuminate\Database\Eloquent\Relations\HasMany && get_class($rel->getRelated()) === $relationModelFqn) return $method->getName();
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {}
        return null;
    }

    protected function relationFromModelName(string $modelBase): string
    {
        $words = preg_split('/(?=[A-Z])/', $modelBase, -1, PREG_SPLIT_NO_EMPTY);
        return Str::plural(!empty($words) ? strtolower(end($words)) : Str::snake($modelBase));
    }

    protected function buildRelationRoute(string $rel, string $relationSlug, string $parentModule): string
    {
        $parts = array_values(array_filter(explode('/', trim($rel, '/'))));

        $relationCount = 0;
        foreach ($parts as $part) {
            if (strtolower($part) === 'relation' || strtolower($part) === 'relations') {
                $relationCount++;
            }
        }

        if ($relationCount > 1) return $this->buildNestedRelationRoute($parts);

        $relationIdx = false;
        foreach ($parts as $idx => $part) {
            if (strtolower($part) === 'relation' || strtolower($part) === 'relations') {
                $relationIdx = $idx;
                break;
            }
        }

        $baseParts = $relationIdx !== false ? array_slice($parts, 0, $relationIdx) : $parts;

        $route = trim(implode('/', array_map('strtolower', $baseParts)) . '/{{' . strtolower($parentModule) . 'ID}}/' . $relationSlug, '/');

        return $route;
    }

    protected function buildNestedRelationRoute(array $parts): string
    {
        $routeSegments = $baseModelStack = [];
        $firstRelationIndex = array_search('Relation', $parts, true);
        for ($i = 0; $i < count($parts) && $parts[$i] !== 'Relation'; $i++) {
            $routeSegments[] = strtolower($parts[$i]);
            $baseModelStack[] = $parts[$i];
        }

        $currentParent = end($baseModelStack);
        $priorRelationChain = [];
        for ($i = $firstRelationIndex; $i !== false && $i < count($parts); $i++) {
            if ($parts[$i] !== 'Relation') continue;
            $relationModelName = $parts[$i + 1] ?? null;
            if (!$relationModelName) continue;

            $parentFqn = empty($priorRelationChain) ? $this->fqnFromSegments($baseModelStack, $currentParent, 'Model') : $this->fqnRelationModel($baseModelStack, array_slice($priorRelationChain, 0, count($priorRelationChain)), end($priorRelationChain));
            $relationFqn = $this->fqnRelationModel($baseModelStack, $priorRelationChain, $relationModelName);
            $relation = $this->guessRelationSlug($parentFqn, $relationFqn) ?: $this->relationFromModelName($relationModelName);
            $routeSegments[] = '{{' . strtolower($currentParent) . 'ID}}';
            $routeSegments[] = Str::snake($relation);
            $priorRelationChain[] = $relationModelName;
            $currentParent = $relationModelName;
            $i++;
        }
        return implode('/', $routeSegments);
    }

    protected function fqnRelationModel(array $baseSegments, array $priorRelationModels, string $targetModel): string
    {
        $parts = array_values(array_filter($baseSegments, fn($s) => $s !== 'Relation'));
        foreach ($priorRelationModels as $m) {
            $parts[] = 'Relation';
            $parts[] = $m;
        }
        $parts[] = 'Relation';
        $parts[] = $targetModel;
        return 'App\\Models\\' . implode('\\', $parts) . '\\' . $targetModel . 'Model';
    }

    protected function toPostmanTree(array $node): array
    {
        $items = [];
        if (isset($node['__CRUD__']) && in_array($node['__CRUD__']['type'], ['model', 'relation_model'], true)) {
            $crud = $node['__CRUD__'];
            $items = array_merge($items, $this->makeCrudRequests(basename($crud['file_path'], 'Model.php'), $crud['route'], $crud['file_path'], ['is_relation' => $crud['type'] === 'relation_model']));
            unset($node['__CRUD__']);
        }

        foreach ($node as $name => $info) {
            if ($info['type'] === 'directory' || $info['type'] === 'relation') {
                $children = $this->toPostmanTree($info['children']);
                if (!empty($children)) $items[] = ['name' => $name, 'item' => $children, 'description' => $info['type'] === 'relation' ? "Relation relations for {$info['parent']}" : "Operations for {$name} module"];
            } elseif ($info['type'] === 'model') {
                $requests = $this->makeCrudRequests($name, $info['route'], $info['file_path'], ['is_relation' => false]);
                if (!empty($requests)) $items[] = ['name' => $name, 'item' => $requests, 'description' => "CRUD operations for {$name}"];
            }
        }
        return $items;
    }

    protected function makeCrudRequests(string $modelName, string $route, string $filePath, array $opts = []): array
    {
        $actions = ['index' => ['method' => 'GET', 'param' => false], 'store' => ['method' => 'POST', 'param' => false], 'show' => ['method' => 'GET', 'param' => true], 'update' => ['method' => 'PUT', 'param' => true], 'destroy' => ['method' => 'DELETE', 'param' => true]];
        $requests = [];
        foreach ($actions as $action => $cfg) {
            $url = $route . ($cfg['param'] ? '/{{id}}' : '');
            $label = ['index' => 'List endpoint', 'show' => 'Get endpoint', 'store' => 'Create endpoint', 'update' => 'Update endpoint', 'destroy' => 'Delete endpoint'][$action];
            $req = ['name' => $label, 'request' => ['method' => $cfg['method'], 'header' => $this->headers($cfg['method']), 'url' => ['raw' => '{{apiURL}}{{version}}/' . ltrim($url, '/'), 'host' => ['{{apiURL}}{{version}}'], 'path' => array_values(array_filter(explode('/', $url)))], 'description' => "{$label} for {$modelName}"]];

            if ($cfg['param']) $req['request']['url']['variable'] = [['key' => 'id', 'value' => '1', 'description' => "{$modelName} ID"]];
            if (in_array($cfg['method'], ['POST', 'PUT'], true)) $req['request']['body'] = ['mode' => 'raw', 'raw' => json_encode($this->jsonBodyParams($filePath, $action), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)];
            if ($cfg['method'] === 'GET' && $action === 'index') $req['request']['url']['query'] = array_merge($this->paginationParams(), $this->filterParams($filePath), $this->includeParams($filePath), $this->fieldParams($filePath), $this->sortParams($filePath));

            if ($this->authEnabled && $this->needsAuth($route)) {
                $req['request']['header'][] = ['key' => 'Authorization', 'value' => 'Bearer {{authToken}}', 'type' => 'text'];
            }

            $requests[] = $req;
        }
        return $requests;
    }

    protected function headers(string $method): array
    {
        $headers = [['key' => 'Accept', 'value' => 'application/json', 'type' => 'text']];
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) $headers[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
        return $headers;
    }

    protected function paginationParams(): array
    {
        return [['key' => 'page', 'value' => '1', 'description' => 'Page number', 'disabled' => true], ['key' => 'limit', 'value' => '15', 'description' => 'Items per page', 'disabled' => true]];
    }

    protected function jsonBodyParams(string $filePath, string $action): array
    {
        $out = [];
        $fieldTrait = $this->fqnFromPath($filePath, 'Field');
        if (!class_exists($fieldTrait) && !trait_exists($fieldTrait)) return $out;

        $modelFqn = str_replace('Field', 'Model', $fieldTrait);
        $table = null;
        if (class_exists($modelFqn)) {
            try { $table = (new $modelFqn)->getTable(); } catch (\Throwable $e) {}
        }
        $columnsMeta = $this->tableColumnsMeta($table);

        try {
            foreach ((new \ReflectionClass($fieldTrait))->getProperties() as $prop) {
                $formAttrs = $prop->getAttributes(\App\Attributes\Model\FormField::class);
                $actionAttrs = $prop->getAttributes(\App\Attributes\Model\ActionType::class);
                if (empty($formAttrs) || empty($actionAttrs)) continue;

                $form = $formAttrs[0]->newInstance();
                $act = $actionAttrs[0]->newInstance();
                if (!in_array($action, $act->actions, true)) continue;

                $name = $prop->getName();
                $value = $this->exampleFromPhpType($name, $prop->getType()?->getName(), $form->type ?? null);
                if ($value === null) $value = $this->exampleFromDbType($name, $columnsMeta[$name]['type'] ?? null, $form->type ?? null);
                if ($value === null) $value = $this->exampleFromNameHeuristics($name, $form->type ?? null);
                $out[$name] = $value;
            }
        } catch (\Throwable $e) {}
        return $out;
    }

    protected function exampleFromPhpType(string $field, ?string $phpType, ?string $formType)
    {
        return match ($phpType) {
            'bool' => true,
            'int' => 1,
            'float' => 123.45,
            'array' => ['key' => 'value'],
            'string' => $this->defaultStringExample($field),
            default => null,
        };
    }

    protected function exampleFromDbType(string $field, ?string $dbTypeStr, ?string $formType)
    {
        return match ($this->normalizeDbType($dbTypeStr)) {
            'int', 'smallint', 'bigint' => 1,
            'tinyint_bool', 'boolean' => true,
            'decimal', 'float', 'double' => 123.45,
            'json' => ['key' => 'value'],
            'datetime', 'timestamp' => '2024-01-01T12:00:00Z',
            'date' => '2024-01-01',
            'time' => '12:34:56',
            default => null,
        };
    }

    protected function exampleFromNameHeuristics(string $field, ?string $formType)
    {
        if (Str::endsWith($field, '_ids')) return [1, 2, 3];
        if (Str::endsWith($field, '_id')) return 1;
        if (Str::startsWith($field, 'is_') || Str::startsWith($field, 'has_') || Str::startsWith($field, 'support_')) return true;
        if (in_array($formType, ['number', 'numeric'], true)) return 1;
        if (in_array($formType, ['checkbox', 'switch', 'boolean'], true)) return true;
        if (in_array($formType, ['file', 'image'], true)) return 'file_placeholder.ext';
        return $this->defaultStringExample($field);
    }

    protected function filterParams(string $filePath): array
    {
        $out = [];
        $modelFqn = $this->fqnFromPath($filePath, 'Model');
        if (!class_exists($modelFqn)) return $out;
        $table = null;
        try { $table = (new $modelFqn)->getTable(); } catch (\Throwable $e) {}
        $comments = $this->tableColumnComments($table);

        try {
            foreach ((new \ReflectionClass($modelFqn))->getProperties() as $prop) {
                $tableColAttrs = $prop->getAttributes(\App\Attributes\Model\TableColumn::class);
                $actionAttrs = $prop->getAttributes(\App\Attributes\Model\ActionType::class);
                if (empty($tableColAttrs) || empty($actionAttrs)) continue;

                $tableCol = $tableColAttrs[0]->newInstance();
                $act = $actionAttrs[0]->newInstance();
                if (in_array('index', $act->actions, true) && in_array('filtering', $tableCol->actions, true)) {
                    $name = $prop->getName();
                    $out[] = ['key' => "filter[{$name}]", 'value' => $this->stringExampleFor($name), 'description' => $comments[$name] ?? "Filter by {$name}", 'disabled' => true];
                }
            }
        } catch (\Throwable $e) {}
        return $out;
    }

    protected function includeParams(string $filePath): array
    {
        $allowed = $this->readModelArrayProperty($filePath, 'allowedRelations');
        return empty($allowed) ? [] : [['key' => 'include', 'value' => implode(',', $allowed), 'description' => 'Allowed includes: ' . implode(', ', $allowed), 'disabled' => true]];
    }

    protected function fieldParams(string $filePath): array
    {
        $allowed = $this->readModelArrayProperty($filePath, 'allowedFields');
        return empty($allowed) ? [] : [['key' => 'fields', 'value' => implode(',', $allowed), 'description' => 'Selectable fields: ' . implode(', ', $allowed), 'disabled' => true]];
    }

    protected function sortParams(string $filePath): array
    {
        $allowed = $this->readModelArrayProperty($filePath, 'sortable');
        return empty($allowed) ? [] : [['key' => 'sort', 'value' => implode(',', $allowed), 'description' => 'Sortable fields: ' . implode(', ', $allowed), 'disabled' => true]];
    }

    protected function stringExampleFor(string $field): string
    {
        $examples = ['status_id' => '1', 'type_id' => '1', 'company_id' => '1', 'driver_id' => '1', 'vehicle_id' => '1', 'brand_id' => '1', 'category_id' => '1', 'license_plate' => '34ABC123', 'year' => '2023', 'color' => 'white', 'phone' => '+905551234567', 'created_at' => '2024-01-01,2024-12-31', 'updated_at' => '2024-01-01,2024-12-31', 'is_available' => 'true', 'support_transfer' => 'true', 'support_rental' => 'true', 'wheelchair_access' => 'true', 'pet_friendly' => 'false'];
        if (Str::endsWith($field, '_id')) return '1';
        if (Str::startsWith($field, 'is_') || Str::startsWith($field, 'support_') || Str::contains($field, ['_access', '_friendly'])) return 'true';
        if (Str::contains($field, ['_at', '_date'])) return '2024-01-01,2024-12-31';
        return $examples[$field] ?? 'example_value';
    }

    protected function defaultStringExample(string $field): string
    {
        $map = ['email' => 'user@example.com', 'phone' => '+905551234567', 'name' => 'Example Name', 'title' => 'Example Title', 'code' => 'ABC123', 'license_plate' => '34ABC123', 'color' => 'white', 'year' => '2023'];
        foreach ($map as $k => $v) {
            if (str_contains($field, $k)) return $v;
        }
        return 'example_value';
    }

    protected function normalizeDbType(?string $type): ?string
    {
        if (!$type) return null;
        $t = strtolower($type);
        if (str_starts_with($t, 'tinyint')) return (preg_match('/tinyint\((\d+)\)/', $t, $m) && (int)$m[1] === 1) ? 'tinyint_bool' : 'smallint';
        if (str_starts_with($t, 'int')) return 'int';
        if (str_starts_with($t, 'bigint')) return 'bigint';
        if (str_starts_with($t, 'smallint')) return 'smallint';
        if (str_starts_with($t, 'bool')) return 'boolean';
        if (str_starts_with($t, 'decimal')) return 'decimal';
        if (str_starts_with($t, 'double')) return 'double';
        if (str_starts_with($t, 'float')) return 'float';
        if (str_starts_with($t, 'json')) return 'json';
        if (str_starts_with($t, 'datetime')) return 'datetime';
        if (str_starts_with($t, 'timestamp')) return 'timestamp';
        if (str_starts_with($t, 'date')) return 'date';
        if (str_starts_with($t, 'time')) return 'time';
        return 'string';
    }

    protected function needsAuth(string $route): bool
    {
        if (!$this->authEnabled) {
            return false;
        }

        foreach (['definition/location/search', 'catalog/availability', 'parameter', 'configuration'] as $p) {
            if (Str::startsWith($route, $p)) return false;
        }
        return true;
    }

    protected function mergeFolders(array $a, array $b): array
    {
        $merged = $a;
        foreach ($b as $folderB) {
            $found = false;
            foreach ($merged as &$folderA) {
                if (isset($folderA['name'], $folderB['name'], $folderA['item'], $folderB['item']) && strtolower($folderA['name']) === strtolower($folderB['name'])) {
                    $folderA['item'] = $this->mergeFolders($folderA['item'], $folderB['item']);
                    $found = true;
                    break;
                }
            }
            if (!$found) $merged[] = $folderB;
        }
        return $merged;
    }

    protected function pruneExcluded(array $items): array
    {
        $allSegs = array_unique(array_merge($this->excludedRouteFirstSegments, array_map('strtolower', (array)config('postman_generator.excluded_route_first_segments', []))));
        $out = [];
        foreach ($items as $it) {
            if (isset($it['name'], $it['item']) && is_array($it['item'])) {
                if ($this->isExcludedDir($it['name'])) continue;
                $it['item'] = $this->pruneExcluded($it['item']);
                if (!empty($it['item'])) $out[] = $it;
                continue;
            }
            if (isset($it['request']['url']['path']) && is_array($it['request']['url']['path']) && in_array(strtolower($it['request']['url']['path'][0] ?? ''), $allSegs, true)) continue;
            $out[] = $it;
        }
        return $out;
    }

    protected function postmanPath(string $fileName): string
    {
        $path = base_path('postman/' . $fileName);
        if (!File::exists(dirname($path))) File::makeDirectory(dirname($path), 0755, true, true);
        return $path;
    }

    protected function collectionDoc(): string
    {
        $authStatus = $this->authEnabled ? 'enabled' : 'disabled';
        return "TransferCab API Collection\n\nThis collection contains all API endpoints for the TransferCab platform.\n\nFeatures:\n- Dynamic model-based endpoints\n- Automatic CRUD operations\n- Filtering, sorting, field selection, includes\n- Raw JSON body generation (typed) for Create/Update\n- Bearer token authentication: {$authStatus}\n\nGenerated on: " . now()->format('Y-m-d H:i:s');
    }

    protected function buildEnvironment(string $env, string $projectName): array
    {
        $parsed = parse_url(config('app.url'));
        $host = $parsed['host'] ?? '';
        $protocol = ($parsed['scheme'] ?? 'https') . '://';
        $parts = explode('.', $host);
        $subDomain = count($parts) > 2 ? $parts[0] . '.' : '';
        $domain = count($parts) > 2 ? implode('.', array_slice($parts, 1)) : $host;

        $values = [
            ['key' => 'protocol', 'value' => $protocol, 'enabled' => true],
            ['key' => 'subDomain', 'value' => $subDomain, 'enabled' => true],
            ['key' => 'domain', 'value' => $domain, 'enabled' => true],
            ['key' => 'path', 'value' => '/api', 'enabled' => true],
            ['key' => 'version', 'value' => '/v1', 'enabled' => true],
            ['key' => 'apiURL', 'value' => '{{protocol}}{{subDomain}}{{domain}}{{path}}', 'enabled' => true],
        ];

        if ($this->authEnabled) {
            $values = array_merge($values, [
                ['key' => 'authEmail', 'value' => 'aksoy@' . $domain, 'enabled' => true],
                ['key' => 'authPassword', 'value' => '19441944Aks%&', 'enabled' => true],
                ['key' => 'authNameFirst', 'value' => 'Fahrettin', 'enabled' => true],
                ['key' => 'authNameLast', 'value' => 'Aksoy', 'enabled' => true],
                ['key' => 'authToken', 'value' => '', 'enabled' => true],
            ]);
        }

        return [
            'id' => (string)Str::uuid(),
            'name' => "{$projectName} " . ucfirst($env),
            'values' => $values,
            '_postman_variable_scope' => 'environment',
            '_postman_exported_at' => now()->toIso8601String(),
            '_postman_exported_using' => 'Postman/10.0.0',
        ];
    }

    protected function countRequests(array $items): int
    {
        $count = 0;
        foreach ($items as $it) {
            if (isset($it['request'])) $count++;
            elseif (isset($it['item'])) $count += $this->countRequests($it['item']);
        }
        return $count;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2, PHP_ROUND_HALF_UP) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2, PHP_ROUND_HALF_UP) . ' KB';
        return $bytes . ' B';
    }

    protected function fqnFromPath(string $filePath, string $suffix): string
    {
        $dir = dirname($filePath);
        $name = basename($filePath, 'Model.php');
        $segments = explode('/', str_replace(app_path('Models') . '/', '', $dir));
        return 'App\\Models\\' . implode('\\', $segments) . '\\' . $name . $suffix;
    }

    protected function fqnFromSegments(array $segments, string $base, string $suffix): string
    {
        return 'App\\Models\\' . implode('\\', array_values(array_filter($segments, fn($s) => $s !== 'Relation'))) . '\\' . $base . $suffix;
    }

    protected function tableColumnComments(?string $table): array
    {
        if (!$table) return [];
        $comments = [];
        try {
            foreach (\DB::select("SHOW FULL COLUMNS FROM {$table}") as $c) {
                $comments[$c->Field] = $c->Comment ?? '';
            }
        } catch (\Throwable $e) {}
        return $comments;
    }

    protected function tableColumnsMeta(?string $table): array
    {
        if (!$table) return [];
        $out = [];
        try {
            foreach (\DB::select("SHOW FULL COLUMNS FROM {$table}") as $c) {
                $out[$c->Field] = ['type' => $c->Type ?? null, 'nullable' => (isset($c->Null) && strtoupper($c->Null) === 'YES'), 'comment' => $c->Comment ?? ''];
            }
        } catch (\Throwable $e) {}
        return $out;
    }

    protected function readModelArrayProperty(string $filePath, string $property): array
    {
        $fqn = $this->fqnFromPath($filePath, 'Model');
        if (!class_exists($fqn)) return [];
        try {
            $ref = new \ReflectionClass($fqn);
            if (!$ref->hasProperty($property)) return [];
            $prop = $ref->getProperty($property);
            $prop->setAccessible(true);
            $val = $prop->getValue(new $fqn);
            return is_array($val) ? $val : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function isExcludedDir(string $dir): bool
    {
        return in_array(strtolower($dir), array_map('strtolower', array_merge($this->excludedDirectories, (array)config('postman_generator.excluded_directories', []))), true);
    }

    protected function isExcludedModelBase(string $base): bool
    {
        if (in_array($base, array_merge($this->excludedModelNames, (array)config('postman_generator.excluded_models', [])), true)) return true;
        foreach (array_merge($this->excludedModelNamePatterns, (array)config('postman_generator.excluded_model_patterns', [])) as $p) {
            if (@preg_match($p, $base) && preg_match($p, $base)) return true;
        }
        return false;
    }

    protected function routeFromRel(string $rel, string $modelBase, bool $appendModel = true): string
    {
        if ($rel === '' || $rel === '/') return $appendModel ? strtolower($modelBase) : '';
        $segments = array_map('strtolower', explode('/', trim($rel, '/')));
        if ($appendModel) $segments[] = strtolower($modelBase);
        return implode('/', array_filter($segments));
    }
}
