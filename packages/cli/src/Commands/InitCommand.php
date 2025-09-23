<?php

declare(strict_types=1);

namespace Lacus\Changesets\Cli\Commands;

use Lacus\Changesets\Config\ConfigLoader;
use Lacus\Changesets\Types\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class InitCommand extends Command
{
    protected static $defaultName = 'init';
    protected static $defaultDescription = 'Initialize changesets in a project';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Initialize changesets in a project')
            ->addOption('config-format', 'f', InputOption::VALUE_OPTIONAL, 'Configuration file format (php, yaml, json)', 'php')
            ->setHelp('This command initializes changesets in your project by creating the necessary configuration and directory structure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $configFormat = $input->getOption('config-format');

        try {
            $io->title('Initializing Changesets');

            // Check if already initialized
            $configLoader = new ConfigLoader();
            $existingConfig = $configLoader->loadConfig('.');

            if ($existingConfig !== Config::default()) {
                $io->warning('Changesets appears to already be initialized in this project.');
                if (!$io->confirm('Do you want to reinitialize?', false)) {
                    $io->info('Initialization cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Create .changeset directory
            if (!is_dir('.changeset')) {
                mkdir('.changeset', 0755, true);
                $io->text('Created .changeset directory');
            }

            // Create default config
            $config = Config::default();
            $configLoader->saveConfig($config, '.', $configFormat);
            $io->text("Created .changeset/config.{$configFormat}");

            // Create .changeset/README.md
            $readmePath = '.changeset/README.md';
            if (!file_exists($readmePath)) {
                $this->createChangesetReadme($readmePath);
                $io->text('Created .changeset/README.md');
            }

            $io->success('Changesets has been initialized successfully!');
            $io->text('');
            $io->text('Next steps:');
            $io->text('1. Run `changesets add` to create your first changeset');
            $io->text('2. Run `changesets version` to apply changesets and update versions');
            $io->text('3. Run `changesets status` to see the current state');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to initialize changesets: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createChangesetReadme(string $filePath): void
    {
        $content = <<<'README'
# Changesets

This directory contains changesets for this project. Changesets are files that describe changes you want to make to your packages.

## Creating a Changeset

To create a changeset, run:

```bash
vendor/bin/changesets add
```

This will open an interactive prompt where you can:
1. Select which packages you want to release
2. Choose the type of version bump (major, minor, patch)
3. Write a summary of the changes

## Changeset Format

Changesets are markdown files with YAML front matter:

```markdown
---
"package-name": major
"another-package": minor
---

Description of the changes made.
```

## Applying Changesets

To apply changesets and update package versions, run:

```bash
vendor/bin/changesets version
```

This will:
1. Combine changesets for each package
2. Update package versions in composer.json files
3. Update changelog files
4. Update internal dependencies

## More Information

For more information about changesets, see the [documentation](https://github.com/lacus/changesets).
README;

        file_put_contents($filePath, $content);
    }
}
