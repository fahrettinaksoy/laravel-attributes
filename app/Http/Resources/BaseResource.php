<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BaseResource extends JsonResource
{
    protected static array $relationCache = [];

    protected int $batchSize = 50;

    protected int $fieldMappingCacheTtl = 3600;

    public function toArray($request): array
    {
        return [
            'type' => $this->resolveResourceType(),
            'id' => (int) $this->resource->getKey(),
            'attributes' => $this->getOptimizedAttributes($request),
            'relationships' => $this->getOptimizedRelationships($request),
            'links' => $this->getLinks($request),
            'meta' => $this->getResourceMeta($request),
        ];
    }

    protected function resolveResourceType(): string
    {
        if (property_exists($this->resource, 'resourceType') && ! empty($this->resource->resourceType)) {
            return (string) $this->resource->resourceType;
        }

        return class_basename($this->resource);
    }

    protected function getOptimizedAttributes($request): array
    {
        $resource = $this->resource;

        if (empty($resource->allowedShowing)) {
            return $this->getFallbackAttributes();
        }

        $cacheKey = $this->getAttributesCacheKey($request);

        return Cache::remember($cacheKey, 300, function () use ($request, $resource) {
            return $this->processAttributes($request, $resource);
        });
    }

    protected function processAttributes($request, $resource): array
    {
        $fieldsRequested = $this->parseRequestedFields($request);
        $allowedFields = $this->getAllowedFields($resource);
        $fieldsToShow = $this->determineFieldsToShow($fieldsRequested, $allowedFields);

        $attributes = [];

        foreach ($resource->allowedShowing as $key => $value) {
            $fieldKey = is_int($key) ? $value : $key;

            if (! in_array($fieldKey, $fieldsToShow, true)) {
                continue;
            }

            $dbField = $this->resolveDbField($key, $value, $fieldKey, $resource);
            $attributeValue = $this->getAttributeValue($resource, $dbField, $fieldKey);

            $attributes[$fieldKey] = $attributeValue;
        }

        return $attributes;
    }

    protected function parseRequestedFields($request): array
    {
        $fieldsParam = $request->query('fields', []);

        if (empty($fieldsParam)) {
            return [];
        }

        return collect($fieldsParam)
            ->flatMap(fn ($item) => explode(',', (string) $item))
            ->map(fn ($field) => trim($field))
            ->filter()
            ->unique()
            ->toArray();
    }

    protected function getAllowedFields($resource): array
    {
        return array_map(
            fn ($k, $v) => is_int($k) ? $v : $k,
            array_keys($resource->allowedShowing),
            $resource->allowedShowing
        );
    }

    protected function determineFieldsToShow(array $requested, array $allowed): array
    {
        return empty($requested) ? $allowed : array_intersect($allowed, $requested);
    }

    protected function resolveDbField($key, $value, string $fieldKey, $resource): string
    {
        if (is_int($key)) {
            return $resource->aliasMapping[$fieldKey] ?? $fieldKey;
        }

        return $value ?: ($resource->aliasMapping[$fieldKey] ?? $fieldKey);
    }

    protected function getAttributeValue($resource, string $dbField, string $fieldKey)
    {
        $attributeValue = $resource->{$dbField} ?? null;

        if ($attributeValue === null && $this->isDateField($fieldKey)) {
            $rawValue = $resource->getRawOriginal($dbField) ?? $resource->getOriginal($dbField);

            if ($rawValue) {
                $attributeValue = $this->formatDateBasedOnField($fieldKey, $rawValue);
            }
        }

        return $this->castAttributeValue($attributeValue, $fieldKey);
    }

    protected function castAttributeValue($value, string $fieldKey)
    {
        if ($value === null) {
            return null;
        }

        if (Str::endsWith($fieldKey, '_id')) {
            return (int) $value;
        }

        if (in_array($fieldKey, ['status', 'is_active', 'enabled'], true)) {
            return (bool) $value;
        }

        if (in_array($fieldKey, ['price', 'amount', 'total'], true)) {
            return (float) $value;
        }

        return $value;
    }

    protected function getOptimizedRelationships($request): array
    {
        $requestedIncludes = $this->parseIncludeParameter($request);

        if (empty($requestedIncludes)) {
            return [];
        }

        $allowedRelations = $this->resource->allowedRelations ?? [];
        $validIncludes = array_intersect($requestedIncludes, $allowedRelations);

        if (empty($validIncludes)) {
            return [];
        }

        return $this->loadAndProcessRelationships($validIncludes, $request);
    }

    protected function parseIncludeParameter($request): array
    {
        $includeParam = $request->query('include');

        if (! $includeParam) {
            return [];
        }

        return array_map('trim', explode(',', $includeParam));
    }

    protected function loadAndProcessRelationships(array $validIncludes, $request): array
    {
        $relationships = [];
        $this->eagerLoadMissingRelations($validIncludes);

        foreach ($validIncludes as $relation) {
            try {
                $relationData = $this->resource->$relation;
                $relationships[$relation] = $this->transformRelationData($relationData, $request);
            } catch (\Exception $e) {
                Log::warning("Failed to load relation: {$relation}", [
                    'error' => $e->getMessage(),
                    'resource_type' => class_basename($this->resource),
                    'resource_id' => $this->resource->getKey(),
                ]);

                $relationships[$relation] = null;
            }
        }

        return $relationships;
    }

    protected function eagerLoadMissingRelations(array $relations): void
    {
        $missingRelations = [];

        foreach ($relations as $relation) {
            if (! $this->resource->relationLoaded($relation)) {
                $missingRelations[] = $relation;
            }
        }

        if (! empty($missingRelations)) {
            $this->resource->load($missingRelations);
        }
    }

    protected function transformRelationData($relationData, $request)
    {
        if ($relationData === null) {
            return null;
        }

        if ($relationData instanceof Model) {
            return $this->transformSingleRelation($relationData, $request);
        }

        if ($relationData instanceof EloquentCollection || $relationData instanceof Collection) {
            return $this->transformCollectionRelation($relationData, $request);
        }

        return $relationData;
    }

    protected function transformSingleRelation(Model $model, $request): array
    {
        $resourceClass = $this->resolveRelationResourceClass($model);

        if ($resourceClass && class_exists($resourceClass)) {
            $resource = new $resourceClass($model);
            $transformed = $resource->toArray($request);

            return $transformed['attributes'] ?? [];
        }

        return (new static($model))->toArray($request)['attributes'] ?? [];
    }

    protected function transformCollectionRelation($collection, $request): array
    {
        if ($collection->isEmpty()) {
            return [];
        }

        $transformed = [];
        $batches = $collection->chunk($this->batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $item) {
                $itemResource = new static($item);
                $transformedItem = $itemResource->toArray($request);
                $transformed[] = $transformedItem['attributes'] ?? [];
            }
        }

        return $transformed;
    }

    protected function resolveRelationResourceClass(Model $model): ?string
    {
        $modelBasename = class_basename($model);
        $resourceClass = "App\\Http\\Resources\\{$modelBasename}Resource";

        return class_exists($resourceClass) ? $resourceClass : null;
    }

    protected function getFallbackAttributes(): array
    {
        $attributes = $this->resource->toArray();

        $sensitiveFields = ['password', 'remember_token', 'api_token'];

        return array_diff_key($attributes, array_flip($sensitiveFields));
    }

    protected function getAttributesCacheKey($request): string
    {
        $fields = $request->query('fields', []);
        $locale = app()->getLocale();
        $resourceKey = $this->resource->getKey();
        $resourceType = class_basename($this->resource);

        $keyData = [
            'resource_type' => $resourceType,
            'resource_id' => $resourceKey,
            'fields' => is_array($fields) ? $fields : [$fields],
            'locale' => $locale,
            'updated_at' => $this->getUpdatedAtTimestamp(),
        ];

        return 'resource_attributes_'.md5(serialize($keyData));
    }

    protected function getUpdatedAtTimestamp(): ?int
    {
        if (!isset($this->resource->updated_at)) {
            return null;
        }

        $updatedAt = $this->resource->updated_at;

        if ($updatedAt instanceof \Carbon\Carbon) {
            return $updatedAt->timestamp;
        }

        if (is_string($updatedAt)) {
            try {
                return \Carbon\Carbon::parse($updatedAt)->timestamp;
            } catch (\Exception $e) {
                \Log::warning('Failed to parse updated_at', [
                    'value' => $updatedAt,
                    'resource_type' => class_basename($this->resource),
                ]);
                return null;
            }
        }

        return null;
    }

    protected function getResourceMeta($request): array
    {
        return [
            'type' => $this->resolveResourceType(),
            'cached_at' => now()->toIso8601String(),
            'locale' => app()->getLocale(),
        ];
    }

    protected function getLinks($request): array
    {
        return [
            'self' => $this->getSelfLink($request),
            //'type' => $this->getTypeLink(),
        ];
    }

    protected function getTypeLink(): string
    {
        $type = Str::plural(Str::snake($this->resolveResourceType()));

        return url("/api/v1/{$type}");
    }

    protected function getSelfLink($request): string
    {
        try {
            if ($request->attributes->get('isPivotRoute')) {
                return $this->buildPivotSelfLink($request);
            }

            return $this->buildMainSelfLink($request);

        } catch (\Exception $e) {
            Log::warning('Failed to generate self link', [
                'error' => $e->getMessage(),
                'resource_type' => class_basename($this->resource),
                'resource_id' => $this->resource->getKey(),
            ]);

            return url("/api/v1/resource/{$this->resource->getKey()}");
        }
    }

    private function buildPivotSelfLink($request): string
    {
        $fullPathWithIds = $request->attributes->get('fullPathWithIds');
        $pivotId = $this->resource->getKey();

        if ($fullPathWithIds) {
            return url("/api/v1/{$fullPathWithIds}/{$pivotId}");
        }

        return $this->buildFallbackPivotLink($request);
    }

    private function buildFallbackPivotLink($request): string
    {
        $segments = $request->segments();

        if (count($segments) >= 6 && $segments[0] === 'api' && $segments[1] === 'v1') {
            $pathSegments = array_slice($segments, 2);

            if (end($pathSegments) && is_numeric(end($pathSegments))) {
                $pathSegments[array_key_last($pathSegments)] = (string) $this->resource->getKey();
            } else {
                $pathSegments[] = (string) $this->resource->getKey();
            }

            $path = implode('/', $pathSegments);

            return url("/api/v1/{$path}");
        }

        return url("/api/v1/pivot/{$this->resource->getKey()}");
    }

    private function buildMainSelfLink($request): string
    {
        $mainModelPath = $request->attributes->get('mainModelPath');

        if ($mainModelPath) {
            return url("/api/v1/{$mainModelPath}/{$this->resource->getKey()}");
        }

        return $this->buildFallbackMainLink($request);
    }

    private function buildFallbackMainLink($request): string
    {
        $segments = $request->segments();

        if (count($segments) >= 3 && $segments[0] === 'api' && $segments[1] === 'v1') {
            $pathSegments = array_slice($segments, 2);

            if (end($pathSegments) && is_numeric(end($pathSegments))) {
                $pathSegments = array_slice($pathSegments, 0, -1);
            }

            $path = implode('/', $pathSegments);

            return url("/api/v1/{$path}/{$this->resource->getKey()}");
        }

        return $this->buildModelBasedLink();
    }

    private function buildModelBasedLink(): string
    {
        $modelClass = get_class($this->resource);
        $pathParts = explode('\\', $modelClass);

        if (count($pathParts) >= 3 && $pathParts[0] === 'App' && $pathParts[1] === 'Models') {
            $pathParts = array_slice($pathParts, 2);

            $lastPart = end($pathParts);
            if (Str::endsWith($lastPart, 'Model')) {
                $pathParts[array_key_last($pathParts)] = Str::before($lastPart, 'Model');
            }

            $path = implode('/', array_map([Str::class, 'snake'], $pathParts));

            return url("/api/v1/{$path}/{$this->resource->getKey()}");
        }

        return url("/api/v1/unknown/{$this->resource->getKey()}");
    }

    public function with($request): array
    {
        return [
            'meta' => [
                'requested_at' => Carbon::now()->toIso8601String(),
                'api_version' => config('api.version', '1.0.0'),
                'environment' => config('app.env'),
                'performance' => [
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true),
                    'queries_count' => $this->getQueriesCount(),
                ],
            ],
        ];
    }

    protected function getQueriesCount(): int
    {
        if (config('app.debug')) {
            return count(\DB::getQueryLog());
        }

        return 0;
    }

    protected function isDateField(string $field): bool
    {
        $dateFields = [
            'birth_date',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
            'deleted_at',
            'published_at',
            'expired_at',
        ];

        return in_array($field, $dateFields, true) ||
            Str::endsWith($field, ['_date', '_at']);
    }

    protected function formatDateBasedOnField(string $field, $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $locale = app()->getLocale();
            $carbon = Carbon::parse($value);

            if (in_array($field, ['created_at', 'updated_at'], true)) {
                return $locale === 'tr'
                    ? $carbon->format('d/m/Y H:i')
                    : $carbon->format('m/d/Y H:i');
            }

            return $locale === 'tr'
                ? $carbon->format('d/m/Y')
                : $carbon->format('m/d/Y');

        } catch (\Exception $e) {
            Log::warning('Date formatting failed', [
                'field' => $field,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function clearCaches(): void
    {
        static::$relationCache = [];
    }
}
