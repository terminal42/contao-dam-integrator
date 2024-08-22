<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Messenger\MessageHandler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Terminal42\ContaoDamIntegrator\AssetHandler;
use Terminal42\ContaoDamIntegrator\Messenger\Message\UpdateMetadataMessage;

#[AsMessageHandler]
class UpdateMetadataMessageHandler
{
    public function __construct(private readonly AssetHandler $assetHandler)
    {
    }

    public function __invoke(UpdateMetadataMessage $message): void
    {
        $this->assetHandler->updateMetadata(new Uuid($message->uuid));
    }
}
