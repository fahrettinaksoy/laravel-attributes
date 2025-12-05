<?php

declare(strict_types=1);

namespace App\Traits;

use App\Attributes\Model\TableColumn;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionProperty;

/**
 * Automatically sets model properties from PHP attributes.
 *
 * Features:
 * - Auto-populates $fillable from protected properties
 * - Sets $allowedFiltering, $allowedSorting, $allowedShowing from TableColumn attributes
 * - Caches reflection data for performance (1 hour TTL)
 *
 * Usage in BaseModel:
 * ```php
 * use SetFieldFromAttributes;
 *
 * public function __construct(array $attributes = [])
 * {
 *     parent::__construct($attributes);
 *     $this->setFillableFromAttributes();
 *     $this->setTableColumnsFromAttributes();
 * }
 * ```
 *
 * Example model:
 * ```php
 * class VehiclesModel extends BaseModel
 * {
 *     #[TableColumn(['showing', 'filtering', 'sorting'])]
 *     protected string $name;
 *
 *     #[TableColumn(['showing', 'filtering'])]
 *     protected string $brand;
 *
 *     protected int $year; // Only fillable, no TableColumn
 * }
 * ```
 *
 * Result:
 * - $fillable = ['name', 'brand', 'year']
 * - $allowedShowing = ['name', 'brand']
 * - $allowedFiltering = ['name', 'brand']
 * - $allowedSorting = ['name']
 */
trait SetFieldFromAttributes
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix for reflection data.
     */
    private const CACHE_PREFIX = 'model_reflection_';

    /**
     * Mapping of TableColumn actions to model property names.
     *
     * @var array<string, string>
     */
    private const COLUMN_ACTIONS = [
        'showing' => 'allowedShowing',
        'filtering' => 'allowedFiltering',
        'sorting' => 'allowedSorting',
    ];

    // ==================== PUBLIC API ====================

    /**
     * Set $fillable property from protected properties.
     *
     * Scans all protected properties and adds their names to $fillable array.
     * Uses caching to avoid repeated reflection.
     */
    protected function setFillableFromAttributes(): void
    {
        $propertyNames = $this->getCachedPropertyNames();

        if (empty($propertyNames)) {
            return;
        }

        $this->fillable = array_values(array_unique($propertyNames));
    }

    /**
     * Set allowed action properties from TableColumn attributes.
     *
     * Processes TableColumn attributes and populates:
     * - $allowedShowing
     * - $allowedFiltering
     * - $allowedSorting
     */
    protected function setTableColumnsFromAttributes(): void
    {
        $metadata = $this->getCachedPropertyMetadata();

        foreach ($metadata as $propertyName => $actions) {
            $this->assignColumnToActions($propertyName, $actions);
        }
    }

    /**
     * Clear reflection cache for this model.
     *
     * Useful for:
     * - Testing
     * - Development (when attributes change)
     * - Cache invalidation
     *
     * @return bool True if cache cleared successfully
     */
    public function clearReflectionCache(): bool
    {
        return Cache::forget($this->cacheKey('properties'))
            && Cache::forget($this->cacheKey('metadata'));
    }

    // ==================== CACHING LAYER ====================

    /**
     * Get property names with caching.
     *
     * @return array<string> Property names
     */
    private function getCachedPropertyNames(): array
    {
        return Cache::remember(
            $this->cacheKey('properties'),
            self::CACHE_TTL,
            fn () => $this->extractPropertyNames()
        );
    }

    /**
     * Get property metadata with caching.
     *
     * @return array<string, array<string>> Property name => actions
     */
    private function getCachedPropertyMetadata(): array
    {
        return Cache::remember(
            $this->cacheKey('metadata'),
            self::CACHE_TTL,
            fn () => $this->extractPropertyMetadata()
        );
    }

    /**
     * Generate cache key for this model class.
     *
     * @param  string  $suffix  Cache key suffix ('properties' or 'metadata')
     * @return string Cache key
     */
    private function cacheKey(string $suffix): string
    {
        return self::CACHE_PREFIX.md5(static::class).'_'.$suffix;
    }

    // ==================== REFLECTION LAYER ====================

    /**
     * Extract protected property names via reflection.
     *
     * Scans the current model class (not parent classes) for all
     * protected properties and returns their names.
     *
     * @return array<string> Property names
     */
    private function extractPropertyNames(): array
    {
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);

        return array_map(
            fn (ReflectionProperty $property) => $property->getName(),
            $properties
        );
    }

    /**
     * Extract property metadata from TableColumn attributes.
     *
     * Scans protected properties for TableColumn attributes and
     * extracts the actions array from each attribute.
     *
     * @return array<string, array<string>> Property name => unique actions
     */
    private function extractPropertyMetadata(): array
    {
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);
        $metadata = [];

        foreach ($properties as $property) {
            $actions = $this->extractActionsFromProperty($property);

            if (! empty($actions)) {
                $metadata[$property->getName()] = array_unique($actions);
            }
        }

        return $metadata;
    }

    /**
     * Extract actions from a property's TableColumn attributes.
     *
     * A property can have multiple TableColumn attributes,
     * each with its own actions array. This method merges all actions.
     *
     * @param  ReflectionProperty  $property  Property to scan
     * @return array<string> Merged and unique actions
     */
    private function extractActionsFromProperty(ReflectionProperty $property): array
    {
        $attributes = $property->getAttributes(TableColumn::class);

        if (empty($attributes)) {
            return [];
        }

        $actions = [];

        foreach ($attributes as $attributeReflection) {
            try {
                $instance = $attributeReflection->newInstance();
                $actions = array_merge($actions, $instance->actions ?? []);
            } catch (\Throwable $e) {
                // Skip attributes that fail to instantiate
                // This prevents one bad attribute from breaking the entire process
                continue;
            }
        }

        return $actions;
    }

    // ==================== ASSIGNMENT LAYER ====================

    /**
     * Assign column to appropriate action arrays.
     *
     * For each action in the column's actions array,
     * add the column name to the corresponding property
     * (e.g., 'filtering' => $allowedFiltering).
     *
     * @param  string  $columnName  Property/column name
     * @param  array<string>  $actions  Actions array from TableColumn
     */
    private function assignColumnToActions(string $columnName, array $actions): void
    {
        foreach (self::COLUMN_ACTIONS as $action => $propertyName) {
            if (! in_array($action, $actions, true)) {
                continue;
            }

            $this->addColumnToProperty($propertyName, $columnName);
        }
    }

    /**
     * Add column to a specific property array.
     *
     * Ensures:
     * - Property exists and is an array
     * - No duplicate entries
     *
     * @param  string  $propertyName  Target property name ('allowedFiltering', etc.)
     * @param  string  $columnName  Column/property name to add
     */
    private function addColumnToProperty(string $propertyName, string $columnName): void
    {
        // Ensure property exists and is array
        $this->ensureArrayProperty($propertyName);

        // Add if not already present
        if (! in_array($columnName, $this->{$propertyName}, true)) {
            $this->{$propertyName}[] = $columnName;
        }
    }

    /**
     * Ensure property exists and is an array.
     *
     * If property doesn't exist or is not an array, initialize it as empty array.
     * This prevents errors when model doesn't declare these properties.
     *
     * @param  string  $propertyName  Property name to check
     */
    private function ensureArrayProperty(string $propertyName): void
    {
        if (! property_exists($this, $propertyName) || ! is_array($this->{$propertyName})) {
            $this->{$propertyName} = [];
        }
    }

    // ==================== DEBUGGING HELPERS ====================

    /**
     * Get reflection cache statistics for this model.
     *
     * Useful for debugging and monitoring cache usage.
     *
     * @return array<string, mixed> Statistics data
     */
    public function getReflectionCacheStats(): array
    {
        $propertiesKey = $this->cacheKey('properties');
        $metadataKey = $this->cacheKey('metadata');

        return [
            'model' => static::class,
            'cache_ttl_seconds' => self::CACHE_TTL,
            'properties_cache' => [
                'key' => $propertiesKey,
                'exists' => Cache::has($propertiesKey),
                'value' => Cache::get($propertiesKey),
            ],
            'metadata_cache' => [
                'key' => $metadataKey,
                'exists' => Cache::has($metadataKey),
                'value' => Cache::get($metadataKey),
            ],
            'current_values' => [
                'fillable' => $this->fillable ?? [],
                'allowedShowing' => $this->allowedShowing ?? [],
                'allowedFiltering' => $this->allowedFiltering ?? [],
                'allowedSorting' => $this->allowedSorting ?? [],
            ],
        ];
    }

    /**
     * Warm up reflection cache for this model.
     *
     * Pre-loads cache to improve first request performance.
     * Useful for:
     * - Application warmup scripts
     * - After deployment
     * - Testing
     */
    public function warmUpReflectionCache(): void
    {
        $this->getCachedPropertyNames();
        $this->getCachedPropertyMetadata();
    }

    /**
     * Get all protected properties with their TableColumn metadata.
     *
     * Returns uncached, fresh data - useful for debugging.
     *
     * @return array<string, array> Property name => metadata
     */
    public function debugPropertyAttributes(): array
    {
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);
        $result = [];

        foreach ($properties as $property) {
            $attributes = $property->getAttributes(TableColumn::class);
            $actions = [];

            foreach ($attributes as $attr) {
                try {
                    $instance = $attr->newInstance();
                    $actions = array_merge($actions, $instance->actions ?? []);
                } catch (\Throwable) {
                    continue;
                }
            }

            $result[$property->getName()] = [
                'type' => $property->getType()?->getName() ?? 'mixed',
                'has_table_column' => ! empty($attributes),
                'actions' => array_unique($actions),
                'will_be_fillable' => true,
                'will_be_showing' => in_array('showing', $actions, true),
                'will_be_filtering' => in_array('filtering', $actions, true),
                'will_be_sorting' => in_array('sorting', $actions, true),
            ];
        }

        return $result;
    }
}
