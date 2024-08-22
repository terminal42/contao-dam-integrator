<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Messenger\Message;

use Contao\CoreBundle\Messenger\Message\LowPriorityMessageInterface;

class UpdateMetadataMessage implements LowPriorityMessageInterface
{
    public function __construct(public string $uuid)
    {
    }
}
