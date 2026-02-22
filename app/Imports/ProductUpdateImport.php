<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class ProductUpdateImport implements OnEachRow, WithHeadingRow
{
    /**
     * @param Row $row
     */
    public function onRow(Row $row)
    {
        $rowData = $row->toArray();

        // Expected columns in Excel: product_id, status (Sold/Buy), types, brand, model, capacity
        // Note: Maatwebsite Excel converts headers to snake_case by default (e.g., 'Product ID' -> 'product_id')

        $productId = isset($rowData['product_id']) ? trim($rowData['product_id']) : null;
        $status = isset($rowData['status']) ? trim($rowData['status']) : null;

        if (!$productId || !$status) {
            return;
        }

        // Default quantity is 1 (each row is one transaction)
        // If a 'quantity' column exists in the upload, use it, otherwise default to 1.
        $quantityChange = isset($rowData['quantity']) && is_numeric($rowData['quantity']) ? (int)$rowData['quantity'] : 1;

        $product = Product::where('product_id', $productId)->first();

        // Create product if missing using Excel data
        if (!$product) {
            $type = $rowData['types'] ?? 'Smartphone';
            $brand = $rowData['brand'] ?? 'Unknown';
            $model = $rowData['model'] ?? 'Unknown';
            $capacity = $rowData['capacity'] ?? 'Unknown';

            $product = new Product();
            $product->product_id = $productId;
            $product->type = $type;
            $product->brand = $brand;
            $product->model = $model;
            $product->capacity = $capacity;
            $product->quantity = 0; // Initialize at 0, transaction will apply change
            $product->save();
        }

        if ($product) {
            // Use transaction to ensure consistency
            DB::transaction(function () use ($product, $status, $quantityChange, $productId) {
                if (strtolower($status) === 'sold') {
                    $product->quantity = max(0, $product->quantity - $quantityChange);
                } elseif (strtolower($status) === 'buy') {
                    $product->quantity += $quantityChange;
                }
                $product->save();

                // Log transaction
                DB::table('product_transactions')->insert([
                    'product_id' => $productId,
                    'type' => ucfirst(strtolower($status)),
                    'quantity' => $quantityChange,
                    'balance_after' => $product->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }
    }
}
