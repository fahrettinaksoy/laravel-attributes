<?php

declare(strict_types=1);

namespace App\Traits;

use App\Attributes\Model\TableColumn;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

trait SetFieldFromAttributes
{
    private const REFLECTION_CACHE_TTL = 3600;

    private const SUPPORTED_ACTIONS = [
        'showing' => 'allowedShowing',
        'filtering' => 'allowedFiltering',
        'sorting' => 'allowedSorting',
    ];

    protected function setFillableFromAttributes(): void
    {
        $protectedPropertyNames = $this->getCachedProtectedPropertyNames();

        if (empty($protectedPropertyNames)) {
            Log::warning('No protected properties found for fillable fields', [
                'class' => static::class,
            ]);

            return;
        }

        $this->fillable = array_values(array_unique($protectedPropertyNames));
    }

    protected function setTableColumnsFromAttributes(): void
    {
        $propertyMetadata = $this->getCachedPropertyMetadata();

        foreach ($propertyMetadata as $propertyData) {
            $this->processPropertyTableColumnMetadata($propertyData);
        }
    }

    private function getCachedProtectedPropertyNames(): array
    {
        $cacheKey = $this->getPropertyNamesCacheKey();

        return Cache::remember($cacheKey, self::REFLECTION_CACHE_TTL, function (): array {
            return $this->extractProtectedPropertyNames();
        });
    }

    private function getCachedPropertyMetadata(): array
    {
        $cacheKey = $this->getPropertyMetadataCacheKey();

        return Cache::remember($cacheKey, self::REFLECTION_CACHE_TTL, function (): array {
            return $this->extractPropertyMetadata();
        });
    }

    private function extractProtectedPropertyNames(): array
    {
        $reflection = new ReflectionClass(static::class);
        $protectedProperties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);

        return array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            $protectedProperties,
        );
    }

    private function extractPropertyMetadata(): array
    {
        $reflection = new ReflectionClass(static::class);
        $protectedProperties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);
        $metadata = [];

        foreach ($protectedProperties as $property) {
            $tableColumnAttributes = $property->getAttributes(TableColumn::class);

            if (empty($tableColumnAttributes)) {
                continue;
            }

            $propertyMetadata = ['name' => $property->getName(), 'actions' => []];

            foreach ($tableColumnAttributes as $attributeReflection) {
                try {
                    $attributeInstance = $attributeReflection->newInstance();
                    $propertyMetadata['actions'] = array_merge($propertyMetadata['actions'], $attributeInstance->actions ?? []);
                } catch (Throwable $exception) {
                    Log::warning('Failed to instantiate table column attribute', [
                        'class' => static::class,
                        'property' => $property->getName(),
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            if (! empty($propertyMetadata['actions'])) {
                $metadata[] = $propertyMetadata;
            }
        }

        return $metadata;
    }

    private function processPropertyTableColumnMetadata(array $propertyData): void
    {
        $columnName = $propertyData['name'] ?? null;
        $actions = $propertyData['actions'] ?? [];

        if (empty($columnName) || empty($actions)) {
            return;
        }

        $this->assignColumnPermissions($columnName, $actions);
    }

    private function assignColumnPermissions(string $columnName, array $actions): void
    {
        $uniqueActions = array_unique($actions);

        foreach (self::SUPPORTED_ACTIONS as $actionName => $propertyName) {
            if (! in_array($actionName, $uniqueActions, true)) {
                continue;
            }

            $this->ensurePropertyIsArray($propertyName);

            if (! in_array($columnName, $this->{$propertyName}, true)) {
                $this->{$propertyName}[] = $columnName;
            }
        }
    }

    private function ensurePropertyIsArray(string $propertyName): void
    {
        if (! property_exists($this, $propertyName)) {
            $this->{$propertyName} = [];

            return;
        }

        if (! is_array($this->{$propertyName})) {
            Log::info('Converting property to array', [
                'class' => static::class,
                'property' => $propertyName,
                'original_type' => gettype($this->{$propertyName}),
            ]);

            $this->{$propertyName} = [];
        }
    }

    private function getPropertyNamesCacheKey(): string
    {
        return sprintf('reflection_property_names_%s', md5(static::class));
    }

    private function getPropertyMetadataCacheKey(): string
    {
        return sprintf('reflection_property_metadata_%s', md5(static::class));
    }

    public function clearReflectionCache(): bool
    {
        $propertyNamesCacheKey = $this->getPropertyNamesCacheKey();
        $propertyMetadataCacheKey = $this->getPropertyMetadataCacheKey();

        $clearedNames = Cache::forget($propertyNamesCacheKey);
        $clearedMetadata = Cache::forget($propertyMetadataCacheKey);

        return $clearedNames && $clearedMetadata;
    }

    public function warmUpReflectionCache(): void
    {
        $this->getCachedProtectedPropertyNames();
        $this->getCachedPropertyMetadata();
    }

    public function getReflectionCacheStats(): array
    {
        $propertyNamesCacheKey = $this->getPropertyNamesCacheKey();
        $propertyMetadataCacheKey = $this->getPropertyMetadataCacheKey();

        return [
            'class' => static::class,
            'cache_ttl' => self::REFLECTION_CACHE_TTL,
            'caches' => [
                'property_names' => [
                    'key' => $propertyNamesCacheKey,
                    'is_cached' => Cache::has($propertyNamesCacheKey),
                ],
                'property_metadata' => [
                    'key' => $propertyMetadataCacheKey,
                    'is_cached' => Cache::has($propertyMetadataCacheKey),
                ],
            ],
        ];
    }
}
