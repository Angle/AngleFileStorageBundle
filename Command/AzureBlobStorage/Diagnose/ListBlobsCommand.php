<?php

namespace Angle\FileStorageBundle\Command\AzureBlobStorage\Diagnose;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Angle\FileStorageBundle\Service\FileStorage;
use Angle\FileStorageBundle\Storage\AzureBlobStorage;
use Angle\FileStorageBundle\Preset\StorageType;


class ListBlobsCommand extends Command
{
    protected static $defaultName = 'angle:file-storage:azure-blob-storage:diagnose:list-blobs';

    /** @var AzureBlobStorage $fileStorage */
    private AzureBlobStorage $fileStorage;

    public function __construct(FileStorage $fileStorage)
    {
        parent::__construct();

        if ($fileStorage->getType() !== StorageType::AZURE_BLOB_STORAGE) {
            throw new \RuntimeException('Cannot initialize an AzureBlobStorage Container when the Storage engine is not type Azure');
        }

        if (!($fileStorage instanceof AzureBlobStorage)) {
            throw new \RuntimeException('Cannot initialize an AzureBlobStorage Container when the Storage engine is not an AzureBlobStorage instance');
        }

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
        // Save the starting time to compute duration of command
        $startTimeProcess = microtime(true);

        $io = new SymfonyStyle($input, $output);
        $io->title('Starting command: List Blobs in Container for Azure Storage Blob.');


        try {
            $blobs = $this->fileStorage->listBlobsInContainer();
        } catch (\Throwable $e) {
            $io->error('Unable to initialize default container: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success(sprintf('Found %d blobs in container', count($blobs)));

        foreach ($blobs as $blob) {
            $io->writeln(sprintf('· %s %s', $blob->getName(), $blob->getUrl()));
        }

        $io->success('The process has finished successfully. The elapsed time has been: '.number_format((microtime(true) - $startTimeProcess), 4).' secs.');
        return Command::SUCCESS;
    }
}
