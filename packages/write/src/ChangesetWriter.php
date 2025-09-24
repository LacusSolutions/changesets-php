<?php

declare(strict_types=1);

namespace Lacus\Changesets\Write;

use Lacus\Changesets\Parse\ChangesetParser;
use Lacus\Changesets\Types\Changeset;

final class ChangesetWriter
{
    private ChangesetParser $parser;

    public function __construct()
    {
        $this->parser = new ChangesetParser();
    }

    public function writeChangeset(Changeset $changeset, string $filePath): void
    {
        $this->parser->writeChangesetFile($changeset, $filePath);
    }

    public function writeChangesetToDirectory(Changeset $changeset, string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $this->generateChangesetFilename($changeset);
        $filePath = $directory . '/' . $filename;

        $this->writeChangeset($changeset, $filePath);

        return $filePath;
    }

    public function generateChangesetFilename(Changeset $changeset): string
    {
        $id = $changeset->id;
        $packages = array_keys($changeset->releases);
        $packageNames = array_map(fn(string $package) => str_replace('/', '-', $package), $packages);
        $packageString = implode('-', $packageNames);

        return "{$id}-{$packageString}.md";
    }

    public function deleteChangeset(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function deleteChangesets(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            $this->deleteChangeset($filePath);
        }
    }

    public function cleanupEmptyChangesets(string $changesetDir): array
    {
        $deletedFiles = [];
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
                $changeset = $this->parser->parseChangesetFile($file->getPathname());
                if ($changeset->isEmpty()) {
                    $this->deleteChangeset($file->getPathname());
                    $deletedFiles[] = $file->getPathname();
                }
            } catch (\Exception $e) {
                // Skip invalid changeset files
                continue;
            }
        }

        return $deletedFiles;
    }
}
