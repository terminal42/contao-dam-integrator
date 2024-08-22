<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;
use Terminal42\ContaoDamIntegrator\AssetHandler;

#[AsCommand('dam-integrator:update-metadata', 'Updates the meta data of a given file path or UUID.')]
class UpdateMetadataCommand extends Command
{
    public function __construct(private readonly AssetHandler $assetHandler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'The file path or UUID.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force re-download of the asset.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getArgument('file');

        if (Uuid::isValid($file)) {
            $file = Uuid::fromString($file);
        }

        if ($input->getOption('force')) {
            $io->comment('Forced re-download of asset.');
            $this->assetHandler->redownloadAsset($file);
        } else {
            $this->assetHandler->updateMetadata($file);
        }

        return Command::SUCCESS;
    }
}
