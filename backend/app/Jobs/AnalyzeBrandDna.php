<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Services\AI\AIServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnalyzeBrandDna implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    private array $assetPaths = [];

    public function __construct(private Brand $brand, array $files)
    {
        // Store uploaded files before job serialization
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store("clients/{$brand->user_id}/brand-assets/dna", 'public');
                $this->assetPaths[] = $path;
            }
        }
    }

    public function handle(AIServiceManager $ai): void
    {
        if (empty($this->assetPaths)) {
            return;
        }

        // Analyze first asset for brand DNA extraction
        $instruction = <<<PROMPT
Analyze this brand image (logo, product photo, or marketing material) and return ONLY a valid JSON object:
{
  "colors": ["#hexcode1", "#hexcode2", "#hexcode3"],
  "tone": "luxury|casual|streetwear|minimal|bold|elegant|playful|professional",
  "photography_style": "editorial|lifestyle|studio|flat_lay|product|mixed",
  "model_preferences": {"ethnicity": "any", "age": "any", "size": "regular"},
  "aesthetic_tags": ["tag1", "tag2", "tag3", "tag4", "tag5"]
}
Return ONLY the JSON.
PROMPT;

        try {
            $result = $ai->analyze($this->assetPaths[0], $instruction);

            $this->brand->update([
                'colors'              => $result['colors'] ?? null,
                'tone'                => $result['tone'] ?? null,
                'photography_style'   => $result['photography_style'] ?? null,
                'model_preferences'   => $result['model_preferences'] ?? null,
                'aesthetic_tags'      => $result['aesthetic_tags'] ?? [],
                'dna_analyzed'        => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Brand DNA analysis failed', [
                'brand_id' => $this->brand->id,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
