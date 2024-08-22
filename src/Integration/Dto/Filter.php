<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

class Filter
{
    /**
     * @var array<mixed>
     */
    public array $extras = [];

    /**
     * @var array<FilterOption>
     */
    private array $options = [];

    public function __construct(
        public readonly string $propertyName,
        public readonly string $label,
    ) {
    }

    public function countOptions(): int
    {
        return \count($this->options);
    }

    /**
     * @return array{propertyName: string, label: string, options: array<array{value: string, label: string}>}
     */
    public function toArray(): array
    {
        // Sort options alphabetically
        usort($this->options, static fn (FilterOption $a, FilterOption $b) => strcasecmp($a->label, $b->label));

        $options = [];

        foreach ($this->options as $option) {
            $options[] = $option->toArray();
        }

        return [
            'propertyName' => $this->propertyName,
            'label' => $this->label,
            'options' => $options,
        ];
    }

    public function addOption(FilterOption $option): self
    {
        $this->options[] = $option;

        return $this;
    }

    public function hasOptions(): bool
    {
        return 0 !== $this->countOptions();
    }

    /**
     * @return array<FilterOption>
     */
    public function allOptions(): array
    {
        return $this->options;
    }
}
