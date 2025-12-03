<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\Resolvers;

use App\Services\ModuleValidation\RoutePathParser;
use App\Services\ModuleValidation\DTO\ResolvedModuleDTO;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PivotRouteResolver implements RouteResolverInterface
{
    public function supports(array $segments): bool
    {
        return RoutePathParser::isPivotRoute($segments);
    }

    public function resolve(array $segments): ResolvedModuleDTO
    {
        $count = count($segments);

        // 1) Sondan başa doğru: ".../{id}/{relation}" patternini bul
        $parentIdIndex = null;
        $parentId = null;
        $originalRelation = null;

        for ($i = $count - 1; $i >= 0; $i--) {
            $segment = $segments[$i];

            if (
                !is_numeric($segment)
                && preg_match('/^[a-zA-Z_-]+$/', $segment)
                && $i > 0
                && is_numeric($segments[$i - 1])
            ) {
                $originalRelation = $segment;          // örn: translations
                $parentId = (int) $segments[$i - 1];   // örn: 4
                $parentIdIndex = $i - 1;               // 4'ün index'i
                break;
            }
        }

        if ($parentIdIndex === null) {
            throw new InvalidArgumentException('Invalid pivot route structure');
        }

        // 2) Parent model path sınırı: önceki numeric id'ye kadar geri git
        // /catalog/product/1/images/4/translations
        //           ^ parentModelEndIndex = 2 (1'in index'i)
        $parentModelEndIndex = $parentIdIndex;
        for ($i = $parentIdIndex - 1; $i >= 0; $i--) {
            if (is_numeric($segments[$i])) {
                $parentModelEndIndex = $i;
                break;
            }
        }

        // 3) Parent model path: numeric olmayan segmentler
        //   -> ['catalog', 'product']
        $parentModelPath = [];
        for ($i = 0; $i < $parentModelEndIndex; $i++) {
            if (!is_numeric($segments[$i])) {
                $parentModelPath[] = $segments[$i];
            }
        }

        if (empty($parentModelPath)) {
            throw new InvalidArgumentException('Cannot resolve parent model path');
        }

        // 4) Base model (ProductModel) + varsa intermediate relation (images) ile main model bul
        //   Base: App\Models\Catalog\Product\ProductModel
        $baseModelClass = RoutePathParser::buildClass($parentModelPath);
        $mainModelClass = $baseModelClass;

        if ($parentModelEndIndex < $parentIdIndex && count($parentModelPath) > 0) {
            $baseModel = new $baseModelClass;
            $intermediateRelation = null;

            // /catalog/product/1/images/4/translations
            // parentModelEndIndex = 2 (1)
            // parentIdIndex       = 4 (4)
            // aradaki index: 3 -> 'images'
            for ($i = $parentModelEndIndex + 1; $i < $parentIdIndex; $i++) {
                if (!is_numeric($segments[$i])) {
                    $intermediateRelation = Str::snake($segments[$i]); // images
                    break;
                }
            }

            if ($intermediateRelation && method_exists($baseModel, $intermediateRelation)) {
                $intermediateRelationObj = $baseModel->{$intermediateRelation}();
                // Burada artık ProductImageModel
                $mainModelClass = get_class($intermediateRelationObj->getRelated());
            } else {
                // /product/1/translations gibi arada extra relation yoksa base'i kullan
                $mainModelClass = $baseModelClass;
            }
        }

        // 5) Asıl relation (translations) üzerinden pivot model (ProductImageTranslationModel) bul
        $relationMethod = Str::snake($originalRelation); // translations
        $parentModel = new $mainModelClass;

        if (!method_exists($parentModel, $relationMethod)) {
            throw new InvalidArgumentException("Relation '{$relationMethod}' not defined on {$mainModelClass}");
        }

        $relationObj = $parentModel->{$relationMethod}();
        $relatedInstance = $relationObj->getRelated();
        $pivotModelClass = get_class($relatedInstance); // ProductImageTranslationModel

        // 6) Relation id: /.../4/translations/{id} ise al
        $relationId = null;
        if (isset($segments[$parentIdIndex + 2]) && is_numeric($segments[$parentIdIndex + 2])) {
            $relationId = (int) $segments[$parentIdIndex + 2];
        }

        // 7) Parent model path'in son parçası tablo adı gibi (product, image vs.)
        $tableName = end($parentModelPath) ?: '';

        // 8) fullPathWithIds: eski middleware ile birebir aynı mantık
        $fullPathWithIds = [];
        $skipNext = false;
        $len = count($segments);

        for ($i = 0; $i < $len; $i++) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            $segment = $segments[$i];

            if (!is_numeric($segment)) {
                $fullPathWithIds[] = $segment;
            } elseif (
                isset($segments[$i + 1]) &&
                !is_numeric($segments[$i + 1]) &&
                preg_match('/^[a-zA-Z_-]+$/', $segments[$i + 1])
            ) {
                $fullPathWithIds[] = $segment;
            }
        }

        return new ResolvedModuleDTO(
            isPivotRoute: true,
            modelClass: $pivotModelClass,
            tableName: $tableName,
            mainModelPath: implode('/', $parentModelPath),
            fullPath: implode('/', $segments),
            parentModelClass: $mainModelClass,
            pivotModelClass: $pivotModelClass,
            relationName: $relationMethod,
            originalRelationName: $originalRelation,
            parentId: $parentId,
            relationId: $relationId,
            fullPathWithIds: implode('/', $fullPathWithIds),
            pivotTableName: $relatedInstance->getTable() // burada cat_product_image_translation
        );
    }
}
