<?php

declare(strict_types=1);

namespace Lacus\Changesets\Cli\Commands;

use Lacus\Changesets\Config\ConfigLoader;
use Lacus\Changesets\Parse\ComposerParser;
use Lacus\Changesets\Types\Changeset;
use Lacus\Changesets\Types\VersionType;
use Lacus\Changesets\Write\ChangesetWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class AddCommand extends Command
{
    protected static $defaultName = 'add';
    protected static $defaultDescription = 'Create a new changeset';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new changeset')
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'Package name')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Version type (major, minor, patch)')
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'Changeset message')
            ->setHelp('This command creates a new changeset file with the specified package and version type.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('Creating Changeset');

            // Load configuration
            $configLoader = new ConfigLoader();
            $config = $configLoader->loadConfig('.');

            // Find packages
            $packages = $this->findPackages();
            if (empty($packages)) {
                $io->error('No packages found. Make sure you have composer.json files in your project.');
                return Command::FAILURE;
            }

            // Get package selection
            $selectedPackages = $this->selectPackages($io, $packages, $input->getOption('package'));
            if (empty($selectedPackages)) {
                $io->info('No packages selected. Changeset creation cancelled.');
                return Command::SUCCESS;
            }

            // Get version types for each package
            $packageVersions = $this->selectVersionTypes($io, $selectedPackages, $input->getOption('type'));

            // Get changeset message
            $message = $this->getChangesetMessage($io, $input->getOption('message'));

            // Create changeset
            $changeset = $this->createChangeset($packageVersions, $message);

            // Write changeset file
            $writer = new ChangesetWriter();
            $changesetDir = '.changeset';
            $filePath = $writer->writeChangesetToDirectory($changeset, $changesetDir);

            $io->success("Changeset created: {$filePath}");
            $io->text('');
            $io->text('Changeset contents:');
            $io->text($writer->generateChangesetFilename($changeset));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create changeset: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function findPackages(): array
    {
        $composerParser = new ComposerParser();
        $composerFiles = $composerParser->findComposerFiles('.');
        $packages = [];

        foreach ($composerFiles as $composerFile) {
            try {
                $package = $composerParser->parsePackage($composerFile);
                $packages[] = $package;
            } catch (\Exception $e) {
                // Skip invalid packages
                continue;
            }
        }

        return $packages;
    }

    private function selectPackages(SymfonyStyle $io, array $packages, ?string $packageName): array
    {
        if ($packageName !== null) {
            $selectedPackage = array_filter($packages, fn($p) => $p->name === $packageName);
            if (empty($selectedPackage)) {
                throw new \InvalidArgumentException("Package '{$packageName}' not found");
            }
            return array_values($selectedPackage);
        }

        $packageNames = array_map(fn($p) => $p->name, $packages);
        $selectedNames = $io->choice(
            'Select packages to include in this changeset',
            $packageNames,
            null,
            true
        );

        return array_filter($packages, fn($p) => in_array($p->name, $selectedNames));
    }

    private function selectVersionTypes(SymfonyStyle $io, array $packages, ?string $versionType): array
    {
        $packageVersions = [];
        $versionTypes = ['major', 'minor', 'patch'];

        foreach ($packages as $package) {
            if ($versionType !== null) {
                $selectedType = VersionType::from($versionType);
            } else {
                $selectedTypeName = $io->choice(
                    "What kind of change is this for {$package->name}?",
                    $versionTypes,
                    'patch'
                );
                $selectedType = VersionType::from($selectedTypeName);
            }

            $packageVersions[$package->name] = $selectedType;
        }

        return $packageVersions;
    }

    private function getChangesetMessage(SymfonyStyle $io, ?string $message): string
    {
        if ($message !== null) {
            return $message;
        }

        return $io->ask('Please enter a summary for this changeset');
    }

    private function createChangeset(array $packageVersions, string $message): Changeset
    {
        $id = $this->generateChangesetId();

        return new Changeset(
            id: $id,
            releases: $packageVersions,
            summary: $message
        );
    }

    private function generateChangesetId(): string
    {
        return uniqid('changeset-', true);
    }
}
