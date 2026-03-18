<?php

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->skip([
        ArrayToFirstClassCallableRector::class,
    ]);
};
