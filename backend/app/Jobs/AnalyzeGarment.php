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

class AnalyzeGarment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(private Garment $garment) {}

    public function handle(AIServiceManager $ai): void
    {
        // Load the active AI config from DB so the correct API key is always used
        $dbConfig = AiProviderConfig::where('is_active', true)->first();
        if ($dbConfig) {
            $ai = $ai->fromConfig($dbConfig);
        }

        $instruction = <<<PROMPT
Analyze this clothing/garment image and return ONLY a valid JSON object with these exact keys:
{
  "category": "shirt|pants|dress|jacket|coat|shoes|bag|accessory|other",
  "gender": "male|female|unisex|kids",
  "season": "spring|summer|autumn|winter|all",
  "colors": ["color1", "color2"],
  "fabric": "cotton|polyester|silk|wool|denim|leather|other|unknown",
  "texture": "smooth|knit|woven|denim|leather|printed|other",
  "style": "casual|formal|streetwear|luxury|sportswear|bohemian|minimalist|other",
  "ai_tags": ["tag1", "tag2", "tag3"]
}
Return ONLY the JSON. No explanation.
PROMPT;

        try {
            $result = $ai->analyze($this->garment->original_image, $instruction);

            $this->garment->update([
                'category' => $result['category'] ?? null,
                'gender'   => $result['gender'] ?? null,
                'season'   => $result['season'] ?? null,
                'colors'   => $result['colors'] ?? [],
                'fabric'   => $result['fabric'] ?? null,
                'texture'  => $result['texture'] ?? null,
                'style'    => $result['style'] ?? null,
                'ai_tags'  => $result['ai_tags'] ?? [],
                'analyzed' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Garment analysis failed', [
                'garment_id' => $this->garment->id,
                'error'      => $e->getMessage(),
            ]);

            // If we've exhausted all retries, mark as analyzed anyway so the
            // garment can still be used for try-on (metadata will just be empty).
            if ($this->attempts() >= $this->tries) {
                $this->garment->update(['analyzed' => true]);
                return; // don't re-throw — non-fatal
            }

            throw $e; // re-throw so the queue retries
        }
    }
}
