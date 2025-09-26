<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BaseCollection extends ResourceCollection
{
    public function __construct($resource = null)
    {
        parent::__construct($resource ?? new Collection([]));
    }

    public function toArray($request): array
    {
        return $this->collection->map(function ($item) use ($request) {
            return (new BaseResource($item))->toArray($request);
        })->toArray();
    }

    public function with($request): array
    {
        $meta = [
            'requested_at' => now()->toIso8601String(),
            'api_version' => config('api.version', '1.0.0'),
            'environment' => config('app.env'),
        ];

        if ($this->resource instanceof LengthAwarePaginator) {
            $meta['pagination'] = [
                'total' => $this->resource->total(),
                'count' => $this->resource->count(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'total_pages' => $this->resource->lastPage(),
                'has_more_pages' => $this->resource->hasMorePages(),
            ];

            return [
                'meta' => $meta,
                'links' => [
                    'first' => $this->resource->url(1),
                    'last' => $this->resource->url($this->resource->lastPage()),
                    'prev' => $this->resource->previousPageUrl(),
                    'next' => $this->resource->nextPageUrl(),
                ],
            ];
        }

        $meta['total'] = $this->collection->count();

        return [
            'meta' => $meta,
        ];
    }
}
