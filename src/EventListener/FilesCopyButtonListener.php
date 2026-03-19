<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\EventListener;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\FilesModel;

#[AsCallback('tl_files', 'list.operations.copy.button')]
class FilesCopyButtonListener
{
    /**
     * Disable copying DAM assets.
     */
    public function __invoke(DataContainerOperation $operation): void
    {
        if (($record = $operation->getRecord()) === null) {
            return;
        }

        if (($model = FilesModel::findByPath($record['id'])) === null) {
            return;
        }

        if (null !== $model->dam_asset_hash) {
            $operation->hide();
        }
    }
}
