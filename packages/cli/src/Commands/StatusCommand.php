<?php

declare(strict_types=1);

namespace Lacus\Changesets\Cli\Commands;

use Lacus\Changesets\Config\ConfigLoader;
use Lacus\Changesets\Read\ChangesetReader;
use Lacus\Changesets\Parse\ComposerParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class StatusCommand extends Command
{
    protected static $defaultName = 'status';
    protected static $defaultDescription = 'Show the current status of changesets';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Show the current status of changesets')
            ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Show verbose output')
            ->setHelp('This command shows the current status of changesets in your project.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $verbose = $input->getOption('verbose');

        try {
            $io->title('Changesets Status');

            // Load configuration
            $configLoader = new ConfigLoader();
            $config = $configLoader->loadConfig('.');

            // Find packages
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

            if (empty($packages)) {
                $io->warning('No packages found. Make sure you have composer.json files in your project.');
                return Command::SUCCESS;
            }

            // Read changesets
            $changesetReader = new ChangesetReader();
            $changesets = $changesetReader->readChangesets('.changeset');

            // Display packages
            $io->section('Packages');
            $packageTable = [];
            foreach ($packages as $package) {
                $packageTable[] = [
                    $package->name,
                    $package->version,
                    $package->private ? 'Yes' : 'No',
                    $package->directory,
                ];
            }
            $io->table(['Name', 'Version', 'Private', 'Directory'], $packageTable);

            // Display changesets
            $io->section('Changesets');
            if (empty($changesets)) {
                $io->text('No changesets found.');
            } else {
                $changesetTable = [];
                foreach ($changesets as $changeset) {
                    $packages = implode(', ', $changeset->getPackages());
                    $changesetTable[] = [
                        $changeset->id,
                        $packages,
                        substr($changeset->summary, 0, 50) . (strlen($changeset->summary) > 50 ? '...' : ''),
                    ];
                }
                $io->table(['ID', 'Packages', 'Summary'], $changesetTable);

                if ($verbose) {
                    $io->text('');
                    $io->section('Changeset Details');
                    foreach ($changesets as $changeset) {
                        $io->text("<comment>{$changeset->id}</comment>");
                        $io->text("Packages: " . implode(', ', $changeset->getPackages()));
                        $io->text("Summary: {$changeset->summary}");
                        $io->text('');
                    }
                }
            }

            // Display summary
            $io->section('Summary');
            $io->text("Total packages: " . count($packages));
            $io->text("Total changesets: " . count($changesets));
            $io->text("Changeset directory: .changeset");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to get status: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
