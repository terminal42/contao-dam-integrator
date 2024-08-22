<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

use Contao\CoreBundle\Picker\PickerConfig;

class AssetFilter
{
    public function __construct(
        public readonly PickerConfig $pickerConfig,
        public readonly string $keywords,
        public readonly FilterCollection $filters,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }
}
