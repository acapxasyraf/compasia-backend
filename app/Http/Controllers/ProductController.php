<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Jobs\ProcessProductUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('product_id', 'like', "%{$search}%");
        }

        if ($request->has('brand') && $request->brand) {
            $query->where('brand', 'like', "%{$request->brand}%");
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', 'like', "%{$request->type}%");
        }

        if ($request->has('model') && $request->model) {
            $query->where('model', 'like', "%{$request->model}%");
        }

        if ($request->has('capacity') && $request->capacity) {
            $query->where('capacity', 'like', "%{$request->capacity}%");
        }

        $sortField = $request->input('sort_by', 'product_id');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['quantity', 'capacity', 'brand', 'model', 'created_at', 'product_id'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Return paginated result
        $perPage = $request->input('per_page', 10);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get available filter options.
     */
    public function filters(Request $request)
    {
        // Base query building helper
        $buildQuery = function() use ($request) {
            return Product::query();
        };

        // Types: Distinct types
        $types = $buildQuery()->distinct()->pluck('type')->sort()->values();

        // Brands: Distinct brands
        $brands = $buildQuery()->distinct()->pluck('brand')->sort()->values();

        // Models: Filtered by Brand (and Type if needed, but let's keep it simple as per requirement "model based on brand")
        $modelsQuery = $buildQuery();
        if ($request->has('brand') && $request->brand) {
            $modelsQuery->where('brand', $request->brand);
        }
        $models = $modelsQuery->distinct()->pluck('model')->sort()->values();

        // Capacities: Filtered by Model (and Brand/Type if implicitly needed, but Model usually implies others)
        $capacitiesQuery = $buildQuery();
        if ($request->has('model') && $request->model) {
            $capacitiesQuery->where('model', $request->model);
        }
        $capacities = $capacitiesQuery->distinct()->pluck('capacity')->sort()->values();

        return response()->json([
            'types' => $types,
            'brands' => $brands,
            'models' => $models,
            'capacities' => $capacities,
        ]);
    }

    /**
     * Handle the Excel upload.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240', // Max 10MB
        ]);

        if ($request->hasFile('file')) {
            // Store the file temporarily
            $path = $request->file('file')->store('temp');

            // Get the absolute path for the job
            $absolutePath = storage_path('app/private/' . $path);

            // Dispatch the job
            ProcessProductUpload::dispatch($absolutePath);

            $this->storeWsMessage([
                'type' => 'upload_queued',
                'path' => $path,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'message' => 'File uploaded successfully. Processing in background.',
                'path' => $path
            ], 200);
        }

        return response()->json(['message' => 'File upload failed.'], 400);
    }

    protected function storeWsMessage(array $payload): void
    {
        $file = storage_path('app/ws_messages.json');
        $messages = [];

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $messages = $decoded;
            }
        }

        $messages[] = [
            'id' => uniqid('msg_', true),
            'payload' => $payload,
        ];

        $messages = array_slice($messages, -50);

        file_put_contents($file, json_encode($messages));
    }
}
