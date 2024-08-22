<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\FilesModel;

#[AsCallback('tl_files', 'list.operations.copy.button')]
class FilesCopyButtonListener
{
    /**
     * Disable copying DAM assets.
     *
     * @param array<string, mixed> $row
     */
    public function __invoke(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $originalCallback = new \tl_files();
        $original = $originalCallback->copyFile($row, $href, $label, $title, $icon, $attributes);

        $model = FilesModel::findByPath($row['id']);

        if (null === $model) {
            return $original;
        }

        if (null !== $model->dam_asset_hash) {
            return '';
        }

        return $original;
    }
}
