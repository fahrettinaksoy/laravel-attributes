<?php

declare(strict_types=1);

namespace App\Models\Catalog\Review;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Review\ReviewField;

use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Accounting\Account\AccountModel;
#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.review.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.review.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.review.delete', 'sort_order' => 3],
    ]
)]
class ReviewModel extends BaseModel
{
    use ReviewField;

    public $table = 'cat_review';
    public $primaryKey = 'review_id';
    public string $defaultSorting = '-review_id';

    public array $allowedRelations = [
        'product',
        'account',
    ];

    public function product(): HasOne
    {
        return $this->hasOne(ProductModel::class, 'product_id', 'product_id');
    }

    public function account(): HasOne
    {
        return $this->hasOne(AccountModel::class, 'account_id', 'account_id');
    }
}
