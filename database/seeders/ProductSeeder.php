<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safe truncate flow
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Product::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = now();
        $dataset = [
            // Based Data
            ['4450', 'Apple', 'iPhone SE', '2GB/16GB', 13],
            ['4451', 'Apple', 'iPhone SE', '2GB/64GB', 20],
            ['4574', 'Apple', 'iPhone SE', '2GB/128GB', 16],
            ['4768', 'Apple', 'iPhone SE', '2GB/32GB', 30],
            ['6039', 'Apple', 'iPhone SE (2020)', '3GB/64GB', 18],
            // Extra Data based on the excel
            // ['6040', 'Apple', 'iPhone 12', '4GB/128GB'],
            // ['6041', 'Apple', 'iPhone 13', '6GB/128GB'],
            // ['6042', 'Apple', 'iPhone 14', '6GB/128GB'],
            // ['6043', 'Apple', 'iPhone 15', '6GB/128GB'],
            // ['6044', 'Apple', 'iPhone 12 Pro', '6GB/128GB'],
            // ['6045', 'Apple', 'iPhone 13 Pro', '6GB/128GB'],
            // ['6046', 'Apple', 'iPhone 14 Pro', '6GB/128GB'],
            // ['6047', 'Apple', 'iPhone 15 Pro', '6GB/128GB'],
            // ['6048', 'Apple', 'iPhone 12 Pro Max', '6GB/128GB'],
            // ['6049', 'Apple', 'iPhone 13 Pro Max', '8GB/128GB'],
            // ['6050', 'Apple', 'iPhone 14 Pro Max', '8GB/128GB'],
            // ['6051', 'Apple', 'iPhone 14', '6GB/128GB'],
            // ['6052', 'Apple', 'iPhone 15', '6GB/128GB'],
            // ['6053', 'Apple', 'iPhone 12 Pro', '6GB/128GB'],
            // ['6054', 'Apple', 'iPhone 13 Pro', '6GB/128GB'],
            // ['6055', 'Apple', 'iPhone 14 Pro', '6GB/128GB'],
            // ['6056', 'Apple', 'iPhone 13 Pro Max', '8GB/128GB'],
            // ['6057', 'Apple', 'iPhone 14 Pro Max', '8GB/128GB'],
            // ['6058', 'Apple', 'iPhone 15', '6GB/128GB'],
            // ['6059', 'Apple', 'iPhone 15 Pro Max', '8GB/128GB'],
            // ['6060', 'Apple', 'iPhone 15 Plus', '8GB/128GB'],
        ];

        $products = [];
        foreach ($dataset as $data) {
            $products[] = [
                'product_id' => $data[0],
                'type' => 'Smartphone',
                'brand' => $data[1],
                'model' => $data[2],
                'capacity' => $data[3],
                'quantity' => $data[4] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Product::insert($products);
    }
}
