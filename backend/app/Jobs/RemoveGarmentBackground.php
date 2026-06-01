<?php

namespace App\Jobs;

use App\Models\AiProviderConfig;
use App\Models\Garment;
use App\Services\AI\AIServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveGarmentBackground implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    public function __construct(private Garment $garment) {}

    public function handle(AIServiceManager $ai): void
    {
        // Skip if already processed
        if ($this->garment->processed_image) {
            return;
        }

        // Load active API key from DB config
        $dbConfig = AiProviderConfig::where('is_active', true)->first();
        if ($dbConfig) {
            $ai = $ai->fromConfig($dbConfig);
        }

        try {
            $processedPath = $ai->removeBackground($this->garment->original_image);
            $this->garment->update(['processed_image' => $processedPath]);
        } catch (\Throwable $e) {
            Log::error('Garment background removal failed', [
                'garment_id' => $this->garment->id,
                'error'      => $e->getMessage(),
            ]);
            // Non-fatal — try-on will fall back to original_image
        }
    }
}
