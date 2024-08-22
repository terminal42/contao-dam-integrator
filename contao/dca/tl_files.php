<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_files']['config']['sql']['keys']['dam_asset_id'] = 'unique';
$GLOBALS['TL_DCA']['tl_files']['config']['sql']['keys']['dam_asset_hash'] = 'index';
$GLOBALS['TL_DCA']['tl_files']['config']['sql']['keys']['dam_asset_integration'] = 'index';
$GLOBALS['TL_DCA']['tl_files']['fields']['dam_asset_id']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['dam_asset_hash']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['dam_asset_integration']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['dam_asset_width']['sql'] = ['type' => 'integer', 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['dam_asset_height']['sql'] = ['type' => 'integer', 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['dam_asset_extra']['sql'] = ['type' => 'text', 'notnull' => false];
