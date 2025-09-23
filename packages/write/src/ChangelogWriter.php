<?php

declare(strict_types=1);

namespace Lacus\Changesets\Write;

use Lacus\Changesets\Types\Release;

final class ChangelogWriter
{
    public function writeChangelog(Release $release, string $filePath): void
    {
        $content = $this->generateChangelogContent($release);

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Failed to write changelog file: {$filePath}");
        }
    }

    public function updateChangelog(Release $release, string $filePath): void
    {
        $existingContent = '';
        if (file_exists($filePath)) {
            $existingContent = file_get_contents($filePath);
        }

        $newContent = $this->generateChangelogContent($release);
        $updatedContent = $this->prependChangelogEntry($existingContent, $newContent);

        if (file_put_contents($filePath, $updatedContent) === false) {
            throw new \RuntimeException("Failed to update changelog file: {$filePath}");
        }
    }

    public function generateChangelogContent(Release $release): string
    {
        $version = $release->newVersion;
        $date = date('Y-m-d');
        $changesetCount = count($release->changesets);
        $changesetText = $changesetCount === 1 ? 'changeset' : 'changesets';

        $content = "## {$version} ({$date})\n\n";
        $content .= "**{$changesetCount} {$changesetText}**\n\n";

        if (!empty($release->changesets)) {
            $content .= "### Changesets\n\n";
            foreach ($release->changesets as $changesetId) {
                $content .= "- {$changesetId}\n";
            }
            $content .= "\n";
        }

        if (!empty($release->dependents)) {
            $content .= "### Dependents\n\n";
            foreach ($release->dependents as $dependent) {
                $content .= "- {$dependent}\n";
            }
            $content .= "\n";
        }

        $content .= "---\n\n";

        return $content;
    }

    private function prependChangelogEntry(string $existingContent, string $newEntry): string
    {
        // Find the first heading to insert before it
        $lines = explode("\n", $existingContent);
        $insertIndex = 0;

        foreach ($lines as $index => $line) {
            if (preg_match('/^#+\s/', $line)) {
                $insertIndex = $index;
                break;
            }
        }

        array_splice($lines, $insertIndex, 0, explode("\n", $newEntry));

        return implode("\n", $lines);
    }

    public function createChangelogFile(string $filePath, string $packageName, string $initialVersion = '0.0.0'): void
    {
        $content = "# Changelog\n\n";
        $content .= "All notable changes to `{$packageName}` will be documented in this file.\n\n";
        $content .= "The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),\n";
        $content .= "and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).\n\n";
        $content .= "## [Unreleased]\n\n";
        $content .= "### Added\n";
        $content .= "- Initial release\n\n";
        $content .= "## [{$initialVersion}] - " . date('Y-m-d') . "\n\n";
        $content .= "### Added\n";
        $content .= "- Initial release\n\n";

        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Failed to create changelog file: {$filePath}");
        }
    }

    public function updateUnreleasedSection(string $filePath, string $changesetId, string $description): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Changelog file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read changelog file: {$filePath}");
        }

        $updatedContent = $this->addToUnreleasedSection($content, $changesetId, $description);

        if (file_put_contents($filePath, $updatedContent) === false) {
            throw new \RuntimeException("Failed to update changelog file: {$filePath}");
        }
    }

    private function addToUnreleasedSection(string $content, string $changesetId, string $description): string
    {
        $lines = explode("\n", $content);
        $unreleasedIndex = -1;
        $addedIndex = -1;

        // Find the [Unreleased] section and the ### Added subsection
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/^##\s*\[Unreleased\]/', $lines[$i])) {
                $unreleasedIndex = $i;
            }
            if ($unreleasedIndex !== -1 && preg_match('/^###\s+Added/', $lines[$i])) {
                $addedIndex = $i;
                break;
            }
        }

        if ($unreleasedIndex === -1 || $addedIndex === -1) {
            // If no unreleased section exists, create it
            $newLines = ["## [Unreleased]\n", "\n", "### Added\n", "- {$changesetId}: {$description}\n", "\n"];
            array_splice($lines, 0, 0, $newLines);
        } else {
            // Add to existing unreleased section
            $newLine = "- {$changesetId}: {$description}\n";
            array_splice($lines, $addedIndex + 1, 0, $newLine);
        }

        return implode("\n", $lines);
    }

    public function moveUnreleasedToVersion(string $filePath, string $version): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Changelog file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read changelog file: {$filePath}");
        }

        $updatedContent = $this->replaceUnreleasedWithVersion($content, $version);

        if (file_put_contents($filePath, $updatedContent) === false) {
            throw new \RuntimeException("Failed to update changelog file: {$filePath}");
        }
    }

    private function replaceUnreleasedWithVersion(string $content, string $version): string
    {
        $date = date('Y-m-d');
        $pattern = '/^##\s*\[Unreleased\]/m';
        $replacement = "## [{$version}] - {$date}";

        return preg_replace($pattern, $replacement, $content);
    }
}
