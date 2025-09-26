<?php

namespace Database\Factories\Catalog\Category;

use App\Models\Catalog\Category\CategoryModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryModelFactory extends Factory
{
    protected $model = CategoryModel::class;

    public function definition(): array
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Clothing', 'description' => 'Fashion and apparel items'],
            ['name' => 'Home & Garden', 'description' => 'Home improvement and garden supplies'],
            ['name' => 'Sports & Outdoors', 'description' => 'Sports equipment and outdoor gear'],
            ['name' => 'Books', 'description' => 'Books and educational materials'],
            ['name' => 'Health & Beauty', 'description' => 'Health and beauty products'],
            ['name' => 'Toys & Games', 'description' => 'Toys and entertainment products'],
            ['name' => 'Automotive', 'description' => 'Car parts and automotive accessories'],
            ['name' => 'Jewelry', 'description' => 'Jewelry and watches'],
            ['name' => 'Food & Beverages', 'description' => 'Food and drink products'],
            ['name' => 'Office Supplies', 'description' => 'Office and business supplies'],
            ['name' => 'Pet Supplies', 'description' => 'Pet care and accessories'],
            ['name' => 'Tools & Hardware', 'description' => 'Tools and hardware equipment'],
            ['name' => 'Music & Movies', 'description' => 'Entertainment media and instruments'],
            ['name' => 'Baby & Kids', 'description' => 'Baby and children products'],
        ];

        $category = $this->faker->randomElement($categories);

        return [
            'name' => $category['name'],
            'code' => strtoupper($this->faker->unique()->lexify('CAT????')),
            'description' => $category['description'],
            'image' => $this->faker->boolean(70) ? 'categories/' . strtolower(str_replace([' ', '&'], ['_', 'and'], $category['name'])) . '.jpg' : null,
            'parent_id' => $this->faker->boolean(30) ? $this->faker->numberBetween(1, 10) : 0,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->boolean(85),
        ];
    }
}
