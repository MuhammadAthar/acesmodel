<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageGeneratorInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OpenAIProvider implements ImageGeneratorInterface
{
    private string $apiKey;
    private string $model  = 'gpt-image-1';
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(?string $apiKey = null, ?string $modelId = null)
    {
        $this->apiKey = $apiKey ?? config('services.openai.key', '');
        if ($modelId) {
            $this->model = $modelId;
        }
    }

    public function generate(string $prompt, ?string $referenceImagePath = null, array $options = []): array
    {
        $payload = [
            'model'  => 'gpt-image-1',
            'prompt' => $prompt,
            'n'      => 1,
            'size'   => $options['size'] ?? '1024x1024',
        ];

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/images/generations", $payload);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        $outputUrl = $data['data'][0]['url'] ?? null;

        // OpenAI returns synchronously, generate a fake prediction id
        $predictionId = 'openai_' . uniqid();

        return [
            'prediction_id' => $predictionId,
            'status'        => 'succeeded',
            'output_url'    => $outputUrl,
        ];
    }

    public function status(string $predictionId): array
    {
        // OpenAI is synchronous, always succeeded if we have a prediction ID
        return ['status' => 'succeeded', 'output_url' => null, 'error' => null];
    }

    public function analyze(string $imagePath, string $instruction): array
    {
        $imageUrl = Storage::disk('public')->url($imagePath);

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/responses", [
                'model' => 'gpt-4o',
                'input' => [
                    [
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'input_image', 'image_url' => $imageUrl],
                            ['type' => 'input_text',  'text'      => $instruction],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI Vision error: ' . $response->body());
        }

        $raw = $response->json('output.0.content.0.text', '{}');
        return json_decode($raw, true) ?? ['raw' => $raw];
    }
}
