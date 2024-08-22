<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator;

use Terminal42\ContaoDamIntegrator\Integration\IntegrationInterface;

class IntegrationCollection
{
    /**
     * @var array<string, IntegrationInterface>
     */
    private array $integrations = [];

    /**
     * @param iterable<string, IntegrationInterface> $integrations
     */
    public function __construct(iterable $integrations)
    {
        $this->integrations = $integrations instanceof \Traversable ? iterator_to_array($integrations) : $integrations;
    }

    /**
     * @return array<string, IntegrationInterface>
     */
    public function all(): array
    {
        return $this->integrations;
    }

    public function count(): int
    {
        return \count($this->integrations);
    }

    public function has(string $integration): bool
    {
        return isset($this->integrations[$integration]);
    }

    public function get(string $integration): IntegrationInterface
    {
        return $this->integrations[$integration] ?? throw new \RuntimeException(\sprintf('Integration "%s" not found.', $integration));
    }
}
