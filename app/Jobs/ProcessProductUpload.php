<?php

namespace App\Jobs;

use App\Imports\ProductUpdateImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ProcessProductUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Excel::import(new ProductUpdateImport, $this->filePath);

            // Notify via WebSocket
            $this->storeWsMessage([
                'type' => 'upload_processed',
                'status' => 'success',
                'message' => 'Excel file processed successfully.',
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            // Log error
            \Log::error('Error processing Excel file: ' . $e->getMessage());

            // Notify failure
            $this->storeWsMessage([
                'type' => 'upload_processed',
                'status' => 'error',
                'message' => 'Error processing file: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);

        } finally {
             // Clean up file if needed. Since we are passing a full path, we need to be careful.
             // If the file is in storage/app/private/temp, we can delete it.
             if (file_exists($this->filePath)) {
                 unlink($this->filePath);
             }
        }
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

        // Keep last 50 messages
        $messages = array_slice($messages, -50);

        file_put_contents($file, json_encode($messages));
    }
}
