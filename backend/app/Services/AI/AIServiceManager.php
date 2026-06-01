<?php

namespace App\Services\AI;

use App\Models\AiProviderConfig;
use App\Services\AI\Contracts\ImageGeneratorInterface;
use App\Services\AI\Providers\ReplicateProvider;
use App\Services\AI\Providers\OpenAIProvider;

class AIServiceManager implements ImageGeneratorInterface
{
    private ImageGeneratorInterface $provider;

    public function __construct()
    {
        $this->provider = $this->resolveProvider(config('services.ai.provider', 'replicate'));
    }

    private function resolveProvider(string $name, ?string $apiToken = null, ?string $modelId = null): ImageGeneratorInterface
    {
        return match ($name) {
            'openai'    => new OpenAIProvider($apiToken),
            'replicate' => new ReplicateProvider($apiToken, $modelId),
            default     => new ReplicateProvider($apiToken, $modelId),
        };
    }

    /** Build a configured instance from an AiProviderConfig DB record. */
    public function fromConfig(AiProviderConfig $config): static
    {
        $clone = clone $this;
        $apiToken = $config->settings['api_key'] ?? null;
        $clone->provider = $this->resolveProvider(
            strtolower($config->provider),
            $apiToken ?: null,
            $config->model_id ?: null,
        );
        return $clone;
    }

    public function using(string $provider): static
    {
        $clone = clone $this;
        $clone->provider = $this->resolveProvider($provider);
        return $clone;
    }

    /** Return the underlying provider instance (for capability checks). */
    public function getProvider(): ImageGeneratorInterface
    {
        return $this->provider;
    }

    public function generate(string $prompt, ?string $referenceImagePath = null, array $options = []): array
    {
        return $this->provider->generate($prompt, $referenceImagePath, $options);
    }

    public function status(string $predictionId): array
    {
        return $this->provider->status($predictionId);
    }

    /**
     * Virtual try-on: place a garment image on a model photo.
     * Delegates to the provider if it supports tryOn(); throws otherwise.
     */
    public function tryOn(string $garmentImagePath, string $category, string $gender = 'female', ?string $baseModelUrl = null, ?string $garmentDescription = null): array
    {
        if (method_exists($this->provider, 'tryOn')) {
            return $this->provider->tryOn($garmentImagePath, $category, $gender, $baseModelUrl, $garmentDescription);
        }
        throw new \RuntimeException('Current AI provider does not support virtual try-on.');
    }

    public function analyze(string $imagePath, string $instruction): array
    {
        return $this->provider->analyze($imagePath, $instruction);
    }

    public function removeBackground(string $storagePath): string
    {
        if (method_exists($this->provider, 'removeBackground')) {
            return $this->provider->removeBackground($storagePath);
        }
        throw new \RuntimeException('Current AI provider does not support background removal.');
    }
}
