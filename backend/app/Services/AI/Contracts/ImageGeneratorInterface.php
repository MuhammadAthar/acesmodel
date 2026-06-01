<?php

namespace App\Services\AI\Contracts;

interface ImageGeneratorInterface
{
    /**
     * Generate an image from a prompt and optional reference image.
     *
     * @param  string       $prompt
     * @param  string|null  $referenceImagePath  local storage path
     * @param  array        $options             provider-specific params
     * @return array{prediction_id: string, status: string, output_url: string|null}
     */
    public function generate(string $prompt, ?string $referenceImagePath = null, array $options = []): array;

    /**
     * Poll the status of a running prediction/job.
     *
     * @return array{status: string, output_url: string|null, error: string|null}
     */
    public function status(string $predictionId): array;

    /**
     * Analyze an image and return structured JSON data.
     */
    public function analyze(string $imagePath, string $instruction): array;
}
