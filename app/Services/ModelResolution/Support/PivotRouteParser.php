<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class PivotRouteParser
{
    public function isPivotRoute(array $pathSegments): bool
    {
        if (count($pathSegments) < 3) {
            return false;
        }

        foreach ($pathSegments as $i => $segment) {
            if (
                is_numeric($segment)
                && isset($pathSegments[$i + 1])
                && !is_numeric($pathSegments[$i + 1])
                && preg_match('/^[a-zA-Z_-]+$/', $pathSegments[$i + 1])
            ) {
                return true;
            }
        }

        return false;
    }

    public function parse(array $pathSegments): object
    {
        $parentIdIndex = null;
        $parentId = null;
        $originalRelation = null;

        // Son relation'ı bul
        for ($i = count($pathSegments) - 1; $i >= 0; $i--) {
            $segment = $pathSegments[$i];

            if (
                !is_numeric($segment)
                && preg_match('/^[a-zA-Z_-]+$/', $segment)
                && $i > 0
                && is_numeric($pathSegments[$i - 1])
            ) {
                $originalRelation = $segment;
                $parentId = (int) $pathSegments[$i - 1];
                $parentIdIndex = $i - 1;
                break;
            }
        }

        if ($parentIdIndex === null) {
            throw new InvalidArgumentException('Invalid pivot route structure');
        }

        $parentModelEndIndex = $this->findParentModelEndIndex($pathSegments, $parentIdIndex);
        $parentModelPath = $this->extractParentModelPath($pathSegments, $parentModelEndIndex);

        $relationId = null;
        if (isset($pathSegments[$parentIdIndex + 2]) && is_numeric($pathSegments[$parentIdIndex + 2])) {
            $relationId = (int) $pathSegments[$parentIdIndex + 2];
        }

        return (object) [
            'parentId' => $parentId,
            'parentIdIndex' => $parentIdIndex,
            'parentModelEndIndex' => $parentModelEndIndex,
            'originalRelation' => $originalRelation,
            'relationMethod' => Str::snake($originalRelation),
            'parentModelPath' => $parentModelPath,
            'relationId' => $relationId,
        ];
    }

    public function buildFullPathWithIds(array $pathSegments): array
    {
        $fullPathWithIds = [];
        $skipNext = false;

        for ($i = 0; $i < count($pathSegments); $i++) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            $segment = $pathSegments[$i];

            if (!is_numeric($segment)) {
                $fullPathWithIds[] = $segment;
            } elseif (
                isset($pathSegments[$i + 1])
                && !is_numeric($pathSegments[$i + 1])
                && preg_match('/^[a-zA-Z_-]+$/', $pathSegments[$i + 1])
            ) {
                $fullPathWithIds[] = $segment;
            }
        }

        return $fullPathWithIds;
    }

    private function findParentModelEndIndex(array $pathSegments, int $parentIdIndex): int
    {
        $parentModelEndIndex = $parentIdIndex;

        for ($i = $parentIdIndex - 1; $i >= 0; $i--) {
            if (is_numeric($pathSegments[$i])) {
                $parentModelEndIndex = $i;
                break;
            }
        }

        return $parentModelEndIndex;
    }

    private function extractParentModelPath(array $pathSegments, int $endIndex): array
    {
        $parentModelPath = [];

        for ($i = 0; $i < $endIndex; $i++) {
            if (!is_numeric($pathSegments[$i])) {
                $parentModelPath[] = $pathSegments[$i];
            }
        }

        return $parentModelPath;
    }
}
