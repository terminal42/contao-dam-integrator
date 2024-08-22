<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addField('dam_enable', 'fop')
    ->applyToPalette('default', 'tl_user_group')
;

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user_group']['fields']['dam_enable'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user_group']['dam_enable'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['multiple' => true, 'csv' => ','],
    'sql' => ['type' => 'text', 'notnull' => false],
];
