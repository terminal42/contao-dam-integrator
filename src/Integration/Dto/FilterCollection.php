<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

class FilterCollection
{
    /**
     * @param array<Filter> $filters
     */
    public function __construct(private array $filters = [])
    {
    }

    /**
     * @return array<array{propertyName: string, label: string, options: array<array{value: string, label: string}>}>
     */
    public function toArray(): array
    {
        $filters = [];

        foreach ($this->filters as $filter) {
            $filters[] = $filter->toArray();
        }

        return $filters;
    }

    public function empty(): bool
    {
        return 0 === $this->count();
    }

    public function count(): int
    {
        return \count($this->filters);
    }

    public function addFilter(Filter $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @return array<Filter>
     */
    public function all(): array
    {
        return $this->filters;
    }
}
