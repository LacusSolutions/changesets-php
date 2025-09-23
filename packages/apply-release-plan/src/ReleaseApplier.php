<?php

declare(strict_types=1);

namespace Lacus\Changesets\ApplyReleasePlan;

use Lacus\Changesets\Parse\ComposerParser;
use Lacus\Changesets\Types\Package;
use Lacus\Changesets\Types\Release;
use Lacus\Changesets\Write\ChangelogWriter;

final class ReleaseApplier
{
    private ComposerParser $composerParser;
    private ChangelogWriter $changelogWriter;

    public function __construct()
    {
        $this->composerParser = new ComposerParser();
        $this->changelogWriter = new ChangelogWriter();
    }

    public function applyReleases(array $releases, array $packages): void
    {
        $packageMap = [];
        foreach ($packages as $package) {
            $packageMap[$package->name] = $package;
        }

        foreach ($releases as $release) {
            $this->applyRelease($release, $packageMap);
        }
    }

    private function applyRelease(Release $release, array $packageMap): void
    {
        $package = $packageMap[$release->name] ?? null;
        if ($package === null) {
            throw new \InvalidArgumentException("Package '{$release->name}' not found");
        }

        // Update package version in composer.json
        $this->updatePackageVersion($package, $release->newVersion);

        // Update internal dependencies
        $this->updateInternalDependencies($package, $release, $packageMap);

        // Update changelog
        $this->updateChangelog($package, $release);
    }

    private function updatePackageVersion(Package $package, string $newVersion): void
    {
        $composerJsonPath = $package->directory . '/composer.json';
        $this->composerParser->updatePackageVersion($composerJsonPath, $newVersion);
    }

    private function updateInternalDependencies(Package $package, Release $release, array $packageMap): void
    {
        $composerJsonPath = $package->directory . '/composer.json';

        foreach ($release->dependents as $dependentName) {
            $dependentPackage = $packageMap[$dependentName] ?? null;
            if ($dependentPackage === null) {
                continue;
            }

            // Update the dependency version in the dependent package
            $this->composerParser->updateDependencyVersion(
                $composerJsonPath,
                $release->name,
                $release->newVersion
            );
        }
    }

    private function updateChangelog(Package $package, Release $release): void
    {
        $changelogPath = $package->directory . '/CHANGELOG.md';

        if (!file_exists($changelogPath)) {
            $this->changelogWriter->createChangelogFile(
                $changelogPath,
                $package->name,
                $release->oldVersion
            );
        }

        $this->changelogWriter->updateChangelog($release, $changelogPath);
    }

    public function createReleaseCommit(array $releases, string $message = null): string
    {
        if ($message === null) {
            $message = $this->generateReleaseCommitMessage($releases);
        }

        // This would typically use Git operations to create a commit
        // For now, we'll just return the message
        return $message;
    }

    private function generateReleaseCommitMessage(array $releases): string
    {
        $packageNames = array_map(fn(Release $release) => $release->name, $releases);
        $packageList = implode(', ', $packageNames);

        return "Release {$packageList}";
    }

    public function createReleaseTags(array $releases, array $packages): array
    {
        $tags = [];
        $packageMap = [];

        foreach ($packages as $package) {
            $packageMap[$package->name] = $package;
        }

        foreach ($releases as $release) {
            $package = $packageMap[$release->name] ?? null;
            if ($package === null) {
                continue;
            }

            $tagName = $this->generateTagName($package->name, $release->newVersion);
            $tags[] = [
                'name' => $tagName,
                'package' => $package->name,
                'version' => $release->newVersion,
                'message' => "Release {$package->name} {$release->newVersion}",
            ];
        }

        return $tags;
    }

    private function generateTagName(string $packageName, string $version): string
    {
        $packageName = str_replace('/', '-', $packageName);
        return "{$packageName}@{$version}";
    }

    public function cleanupChangesets(array $changesetFiles): void
    {
        foreach ($changesetFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function validateReleases(array $releases, array $packages): array
    {
        $errors = [];
        $packageMap = [];

        foreach ($packages as $package) {
            $packageMap[$package->name] = $package;
        }

        foreach ($releases as $release) {
            $package = $packageMap[$release->name] ?? null;
            if ($package === null) {
                $errors[] = "Package '{$release->name}' not found";
                continue;
            }

            // Validate composer.json exists
            $composerJsonPath = $package->directory . '/composer.json';
            if (!file_exists($composerJsonPath)) {
                $errors[] = "Composer.json not found for package '{$release->name}' at {$composerJsonPath}";
            }

            // Validate version format
            if (!$this->isValidVersion($release->newVersion)) {
                $errors[] = "Invalid version format '{$release->newVersion}' for package '{$release->name}'";
            }

            // Validate changesets
            if (empty($release->changesets)) {
                $errors[] = "No changesets found for package '{$release->name}'";
            }
        }

        return $errors;
    }

    private function isValidVersion(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.-]+)?(\+[a-zA-Z0-9.-]+)?$/', $version) === 1;
    }

    public function getReleaseSummary(array $releases): array
    {
        $summary = [
            'totalReleases' => count($releases),
            'packages' => [],
            'versionTypes' => [
                'major' => 0,
                'minor' => 0,
                'patch' => 0,
            ],
            'changesets' => [],
        ];

        foreach ($releases as $release) {
            $summary['packages'][] = $release->name;
            $summary['versionTypes'][$release->type]++;
            $summary['changesets'] = array_merge($summary['changesets'], $release->changesets);
        }

        $summary['changesets'] = array_unique($summary['changesets']);
        $summary['changesetCount'] = count($summary['changesets']);

        return $summary;
    }
}
