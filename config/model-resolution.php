<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Model Resolution Configuration
    |--------------------------------------------------------------------------
    |
    | Bu ayarlar, API route'larınızdan otomatik model çözümlemesi için
    | kullanılır. Model namespace, suffix ve diğer davranışları buradan
    | özelleştirebilirsiniz.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Base Namespace
    |--------------------------------------------------------------------------
    |
    | Model'lerin bulunduğu base namespace. Varsayılan Laravel konvansiyonu
    | olan App\Models kullanılır. Eğer farklı bir namespace yapınız varsa
    | (örn: App\Domain\Models) buradan değiştirebilirsiniz.
    |
    */

    'base_namespace' => env('MODEL_RESOLUTION_NAMESPACE', 'App\\Models'),

    /*
    |--------------------------------------------------------------------------
    | Model Suffix
    |--------------------------------------------------------------------------
    |
    | Model class'larının sonuna eklenen suffix. Örneğin:
    | - "Model" → VehiclesModel
    | - "" (boş) → Vehicles
    | - "Entity" → VehiclesEntity
    |
    */

    'model_suffix' => env('MODEL_RESOLUTION_SUFFIX', 'Model'),

    /*
    |--------------------------------------------------------------------------
    | Exception Routes
    |--------------------------------------------------------------------------
    |
    | Bu route'lar model resolution'dan hariç tutulur. Özel endpoint'ler,
    | auth route'ları veya farklı bir mantığa sahip route'lar için kullanılır.
    |
    | Not: Route'lar /api/v1 prefix'inden sonraki kısmıdır.
    | Örnek: /api/v1/definition/location/search → definition/location/search
    |
    */

    'exception_routes' => [
        // Definition endpoints
        'definition/location/search',
        'definition/geocode',
        'definition/autocomplete',

        // Catalog special endpoints
        'catalog/availability/check',
        'catalog/price/calculate',
        'catalog/search',

        // Authentication
        'auth/login',
        'auth/register',
        'auth/logout',
        'auth/refresh',
        'auth/me',

        // Health & Monitoring
        'health',
        'health/check',
        'ping',
        'status',

        // Webhooks
        'webhook',
        'webhooks',
        'callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Model resolution sonuçları cache'lenir. Bu sayede her request'te
    | yeniden hesaplama yapılmaz ve performance artar.
    |
    */

    'cache' => [
        /*
        | Cache aktif/pasif
        */
        'enabled' => env('MODEL_RESOLUTION_CACHE_ENABLED', true),

        /*
        | Cache süresi (saniye cinsinden)
        | Varsayılan: 3600 saniye (1 saat)
        */
        'ttl' => env('MODEL_RESOLUTION_CACHE_TTL', 3600),

        /*
        | Cache key prefix'i
        | Redis/Memcached key'lerinde kullanılır
        */
        'prefix' => env('MODEL_RESOLUTION_CACHE_PREFIX', 'model_resolution'),

        /*
        | Cache driver
        | null ise varsayılan cache driver kullanılır
        | Seçenekler: 'redis', 'memcached', 'file', 'array', null
        */
        'driver' => env('MODEL_RESOLUTION_CACHE_DRIVER', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Segments
    |--------------------------------------------------------------------------
    |
    | Model resolution için gereken minimum URL segment sayısı.
    | Varsayılan: 3 (/api/v1/model yapısı için)
    |
    | Örnek:
    | - /api/v1 → 2 segment (işlenmez)
    | - /api/v1/catalog → 3 segment (işlenir)
    | - /api/v1/catalog/vehicles → 4 segment (işlenir)
    |
    */

    'minimum_segments' => env('MODEL_RESOLUTION_MIN_SEGMENTS', 3),

    /*
    |--------------------------------------------------------------------------
    | API Prefix Configuration
    |--------------------------------------------------------------------------
    |
    | API route'larınızın prefix'i. Model resolution bu prefix'ten sonraki
    | segment'leri işler.
    |
    */

    'api_prefix' => [
        /*
        | API prefix (örn: 'api')
        */
        'prefix' => env('API_PREFIX', 'api'),

        /*
        | API versiyonu (örn: 'v1')
        */
        'version' => env('API_VERSION', 'v1'),

        /*
        | Skip edilecek segment sayısı
        | Örnek: /api/v1/... için 2 (api ve v1 skip edilir)
        */
        'skip_segments' => env('MODEL_RESOLUTION_SKIP_SEGMENTS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Model resolution sırasında oluşan hataların ve uyarıların
    | loglanması için ayarlar.
    |
    */

    'logging' => [
        /*
        | Log channel
        | null ise varsayılan log channel kullanılır
        */
        'channel' => env('MODEL_RESOLUTION_LOG_CHANNEL', null),

        /*
        | Failed resolution'ları logla
        */
        'log_failures' => env('MODEL_RESOLUTION_LOG_FAILURES', true),

        /*
        | Successful resolution'ları logla (debug için)
        */
        'log_success' => env('MODEL_RESOLUTION_LOG_SUCCESS', false),

        /*
        | Cache hit/miss'leri logla (debug için)
        */
        'log_cache' => env('MODEL_RESOLUTION_LOG_CACHE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Debug
    |--------------------------------------------------------------------------
    |
    | Geliştirme ortamı için debug ayarları.
    |
    */

    'debug' => [
        /*
        | Debug mode - Detaylı hata mesajları ve loglar
        */
        'enabled' => env('MODEL_RESOLUTION_DEBUG', env('APP_DEBUG', false)),

        /*
        | Request'e debug bilgisi ekle (header olarak)
        */
        'add_headers' => env('MODEL_RESOLUTION_DEBUG_HEADERS', false),

        /*
        | Execution time'ı ölç ve logla
        */
        'measure_performance' => env('MODEL_RESOLUTION_MEASURE_PERFORMANCE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Resolvers
    |--------------------------------------------------------------------------
    |
    | Varsayılan resolver'lara ek olarak özel resolver'larınızı
    | buradan ekleyebilirsiniz.
    |
    | Format: [ResolverClass::class, 'priority' => 100]
    | Priority: Düşük sayı önce çalışır (pivot: 10, main: 100)
    |
    */

    'custom_resolvers' => [
        // Örnek:
        // [
        //     'class' => \App\Services\CustomResolver::class,
        //     'priority' => 5,  // Pivot'tan önce çalışır
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Path Transformers
    |--------------------------------------------------------------------------
    |
    | Path segment'lerini model path'e dönüştürürken kullanılacak
    | transformation kuralları.
    |
    */

    'transformers' => [
        /*
        | Segment case transformation
        | Seçenekler: 'studly', 'camel', 'snake', 'kebab'
        */
        'case' => 'studly',

        /*
        | Özel segment dönüşümleri
        | Belirli segment'leri farklı şekilde dönüştürmek için
        */
        'custom_mappings' => [
            // Örnek:
            // 'vehicles' => 'Vehicle',
            // 'categories' => 'Category',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Güvenlik ile ilgili ayarlar.
    |
    */

    'security' => [
        /*
        | İzin verilen model namespace'leri (whitelist)
        | Boş array = tüm namespace'ler izinli
        */
        'allowed_namespaces' => [
            // 'App\\Models\\Catalog',
            // 'App\\Models\\Definition',
        ],

        /*
        | İzin verilmeyen model namespace'leri (blacklist)
        */
        'blocked_namespaces' => [
            // 'App\\Models\\Admin',
            // 'App\\Models\\Internal',
        ],

        /*
        | Maximum path depth (güvenlik için)
        | Örnek: catalog/vehicles/brands/models → depth: 4
        */
        'max_path_depth' => env('MODEL_RESOLUTION_MAX_DEPTH', 10),
    ],
];


