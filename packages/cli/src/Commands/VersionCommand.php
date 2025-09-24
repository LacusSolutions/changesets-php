<?php

declare(strict_types=1);

namespace Lacus\Changesets\Cli\Commands;

use Lacus\Changesets\AssembleReleasePlan\ReleasePlanner;
use Lacus\Changesets\ApplyReleasePlan\ReleaseApplier;
use Lacus\Changesets\Config\ConfigLoader;
use Lacus\Changesets\Read\ChangesetReader;
use Lacus\Changesets\Parse\ComposerParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class VersionCommand extends Command
{
    protected static $defaultName = 'version';
    protected static $defaultDescription = 'Apply changesets and update versions';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Apply changesets and update versions')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show what would be done without making changes')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit changes after applying')
            ->setHelp('This command applies changesets and updates package versions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $commit = $input->getOption('commit');

        try {
            $io->title('Applying Changesets');

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

            if (empty($changesets)) {
                $io->info('No changesets found. Nothing to do.');
                return Command::SUCCESS;
            }

            // Plan releases
            $releasePlanner = new ReleasePlanner();
            $releases = $releasePlanner->planReleases($changesets, $packages);

            if (empty($releases)) {
                $io->info('No releases to apply. Nothing to do.');
                return Command::SUCCESS;
            }

            // Display planned releases
            $io->section('Planned Releases');
            $releaseTable = [];
            foreach ($releases as $release) {
                $releaseTable[] = [
                    $release->name,
                    $release->oldVersion,
                    $release->newVersion,
                    $release->type,
                    count($release->changesets),
                ];
            }
            $io->table(['Package', 'Old Version', 'New Version', 'Type', 'Changesets'], $releaseTable);

            if ($dryRun) {
                $io->info('Dry run mode - no changes were made.');
                return Command::SUCCESS;
            }

            // Apply releases
            $releaseApplier = new ReleaseApplier();
            $releaseApplier->applyReleases($releases, $packages);

            $io->success('Releases applied successfully!');

            // Show summary
            $summary = $releaseApplier->getReleaseSummary($releases);
            $io->text('');
            $io->text("Applied {$summary['totalReleases']} releases:");
            foreach ($summary['packages'] as $package) {
                $io->text("  - {$package}");
            }

            if ($commit) {
                $commitMessage = $releaseApplier->createReleaseCommitMessage($releases);
                $io->text('');
                $io->text("Commit message: {$commitMessage}");
                $io->note('You may want to commit these changes manually.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to apply changesets: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
