<?php

namespace Angle\FileStorageBundle\Command\AzureBlobStorage;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Angle\FileStorageBundle\Service\FileStorage;
use Angle\FileStorageBundle\Storage\AzureBlobStorage;
use Angle\FileStorageBundle\Preset\StorageType;


class InitializeContainerCommand extends Command
{
    protected static $defaultName = 'angle:file-storage:azure-blob-storage:initialize-container';

    /** @var FileStorage $fileStorage */
    private FileStorage $fileStorage;

    public function __construct(FileStorage $fileStorage)
    {
        parent::__construct();

        $this->fileStorage = $fileStorage;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Initialize the Container to be used in Azure Storage Blob services');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->fileStorage->getType() !== StorageType::AZURE_BLOB_STORAGE) {
            throw new \RuntimeException('Cannot initialize an AzureBlobStorage Container when the Storage engine is not type Azure');
        }

        if (!($this->fileStorage->getStorageEngine() instanceof AzureBlobStorage)) {
            throw new \RuntimeException('Cannot initialize an AzureBlobStorage Container when the Storage engine is not an AzureBlobStorage instance');
        }

        // Save the starting time to compute duration of command
        $startTimeProcess = microtime(true);

        $io = new SymfonyStyle($input, $output);
        $io->title('Starting command: Initialize Container for Azure Storage Blob.');


        try {
            /** @var AzureBlobStorage $engine */
            $engine = $this->fileStorage->getStorageEngine();
            $engine->createBlobContainer();
            $engine->setBlobContainerAcl(AzureBlobStorage::ACL_NONE); // no public access allowed
        } catch (\Throwable $e) {
            $io->error('Unable to initialize default container: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('The process has finished successfully. The elapsed time has been: '.number_format((microtime(true) - $startTimeProcess), 4).' secs.');
        return Command::SUCCESS;
    }
}
