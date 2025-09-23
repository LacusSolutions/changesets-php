<?php

declare(strict_types=1);

namespace Lacus\Changesets\Read;

use Lacus\Changesets\Parse\ChangesetParser;
use Lacus\Changesets\Types\Changeset;

final class ChangesetReader
{
    private ChangesetParser $parser;

    public function __construct()
    {
        $this->parser = new ChangesetParser();
    }

    public function readChangeset(string $filePath): Changeset
    {
        return $this->parser->parseChangesetFile($filePath);
    }

    public function readChangesets(string $changesetDir): array
    {
        if (!is_dir($changesetDir)) {
            return [];
        }

        $changesets = [];
        $iterator = new \DirectoryIterator($changesetDir);

        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }

            $extension = $file->getExtension();
            if (!in_array($extension, ['md', 'markdown'], true)) {
                continue;
            }

            try {
                $changeset = $this->readChangeset($file->getPathname());
                $changesets[] = $changeset;
            } catch (\Exception $e) {
                // Skip invalid changeset files
                continue;
            }
        }

        return $changesets;
    }

    public function readChangesetsFromPattern(string $pattern): array
    {
        $files = glob($pattern);
        $changesets = [];

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            try {
                $changeset = $this->readChangeset($file);
                $changesets[] = $changeset;
            } catch (\Exception $e) {
                // Skip invalid changeset files
                continue;
            }
        }

        return $changesets;
    }

    public function findChangesetFiles(string $changesetDir): array
    {
        if (!is_dir($changesetDir)) {
            return [];
        }

        $files = [];
        $iterator = new \DirectoryIterator($changesetDir);

        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }

            $extension = $file->getExtension();
            if (in_array($extension, ['md', 'markdown'], true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function getChangesetCount(string $changesetDir): int
    {
        return count($this->findChangesetFiles($changesetDir));
    }

    public function hasChangesets(string $changesetDir): bool
    {
        return $this->getChangesetCount($changesetDir) > 0;
    }

    public function validateChangesets(string $changesetDir): array
    {
        $errors = [];
        $changesets = $this->readChangesets($changesetDir);

        foreach ($changesets as $changeset) {
            $changesetErrors = $this->parser->validateChangeset($changeset);
            if (!empty($changesetErrors)) {
                $errors[$changeset->id] = $changesetErrors;
            }
        }

        return $errors;
    }

    public function getChangesetIds(string $changesetDir): array
    {
        $changesets = $this->readChangesets($changesetDir);
        return array_map(fn(Changeset $changeset) => $changeset->id, $changesets);
    }

    public function getChangesetsForPackage(string $changesetDir, string $packageName): array
    {
        $changesets = $this->readChangesets($changesetDir);

        return array_filter($changesets, function (Changeset $changeset) use ($packageName) {
            return $changeset->hasReleaseForPackage($packageName);
        });
    }

    public function getPackagesInChangesets(string $changesetDir): array
    {
        $changesets = $this->readChangesets($changesetDir);
        $packages = [];

        foreach ($changesets as $changeset) {
            $packages = array_merge($packages, $changeset->getPackages());
        }

        return array_unique($packages);
    }
}
