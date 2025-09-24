<?php

declare(strict_types=1);

namespace Lacus\Changesets\AssembleReleasePlan;

use Lacus\Changesets\GetDependentsGraph\DependencyGraph;
use Lacus\Changesets\Types\Changeset;
use Lacus\Changesets\Types\Package;
use Lacus\Changesets\Types\Release;
use Lacus\Changesets\Types\Version;
use Lacus\Changesets\Types\VersionType;

final class ReleasePlanner
{
    private DependencyGraph $dependencyGraph;

    public function __construct()
    {
        $this->dependencyGraph = new DependencyGraph();
    }

    public function planReleases(array $changesets, array $packages): array
    {
        $this->dependencyGraph->addPackages($packages);
        $this->dependencyGraph->buildGraph();

        $packageVersions = $this->getCurrentVersions($packages);
        $packageChangesets = $this->groupChangesetsByPackage($changesets);
        $releases = [];

        foreach ($packages as $package) {
            $packageName = $package->name;
            $changesetsForPackage = $packageChangesets[$packageName] ?? [];

            if (empty($changesetsForPackage)) {
                continue;
            }

            $versionType = $this->determineVersionType($changesetsForPackage);
            $oldVersion = $packageVersions[$packageName];
            $newVersion = $this->calculateNewVersion($oldVersion, $versionType);

            $releases[] = new Release(
                name: $packageName,
                type: $versionType->value,
                oldVersion: $oldVersion,
                newVersion: $newVersion,
                changesets: array_map(fn(Changeset $c) => $c->id, $changesetsForPackage),
                dependents: $this->getDependentsToUpdate($packageName, $versionType)
            );
        }

        return $this->sortReleasesByDependencies($releases);
    }

    private function getCurrentVersions(array $packages): array
    {
        $versions = [];
        foreach ($packages as $package) {
            $versions[$package->name] = $package->version;
        }
        return $versions;
    }

    private function groupChangesetsByPackage(array $changesets): array
    {
        $grouped = [];

        foreach ($changesets as $changeset) {
            foreach ($changeset->releases as $packageName => $versionType) {
                if (!isset($grouped[$packageName])) {
                    $grouped[$packageName] = [];
                }
                $grouped[$packageName][] = $changeset;
            }
        }

        return $grouped;
    }

    private function determineVersionType(array $changesets): VersionType
    {
        $highestType = VersionType::NONE;

        foreach ($changesets as $changeset) {
            foreach ($changeset->releases as $versionType) {
                if ($versionType->getBumpValue() > $highestType->getBumpValue()) {
                    $highestType = $versionType;
                }
            }
        }

        return $highestType;
    }

    private function calculateNewVersion(string $currentVersion, VersionType $versionType): string
    {
        $version = Version::fromString($currentVersion);
        $newVersion = $version->bump($versionType);
        return $newVersion->toString();
    }

    private function getDependentsToUpdate(string $packageName, VersionType $versionType): array
    {
        $dependents = $this->dependencyGraph->getAllDependents($packageName);
        $dependentsToUpdate = [];

        foreach ($dependents as $dependentName) {
            $dependentPackage = $this->dependencyGraph->getPackage($dependentName);
            if ($dependentPackage === null) {
                continue;
            }

            // Check if the dependent depends on this package
            $dependencyVersion = $dependentPackage->getDependencyVersion($packageName);
            if ($dependencyVersion === null) {
                continue;
            }

            // Determine if we need to update the dependent
            if ($this->shouldUpdateDependent($packageName, $dependentName, $versionType)) {
                $dependentsToUpdate[] = $dependentName;
            }
        }

        return $dependentsToUpdate;
    }

    private function shouldUpdateDependent(string $packageName, string $dependentName, VersionType $versionType): bool
    {
        $dependentPackage = $this->dependencyGraph->getPackage($dependentName);
        if ($dependentPackage === null) {
            return false;
        }

        $dependencyVersion = $dependentPackage->getDependencyVersion($packageName);
        if ($dependencyVersion === null) {
            return false;
        }

        // If the package is having a major version bump, we need to update dependents
        if ($versionType === VersionType::MAJOR) {
            return true;
        }

        // For minor and patch versions, check if the dependent's version constraint allows it
        return $this->isVersionCompatible($dependencyVersion, $versionType);
    }

    private function isVersionCompatible(string $versionConstraint, VersionType $versionType): bool
    {
        // Simple version constraint parsing - this could be enhanced
        if (str_starts_with($versionConstraint, '^')) {
            // Caret constraint - compatible with minor and patch updates
            return $versionType !== VersionType::MAJOR;
        }

        if (str_starts_with($versionConstraint, '~')) {
            // Tilde constraint - compatible with patch updates only
            return $versionType === VersionType::PATCH;
        }

        // Exact version - not compatible with any updates
        return false;
    }

    private function sortReleasesByDependencies(array $releases): array
    {
        $releaseMap = [];
        foreach ($releases as $release) {
            $releaseMap[$release->name] = $release;
        }

        $sorted = [];
        $visited = [];

        foreach ($releases as $release) {
            $this->sortRelease($release, $releaseMap, $sorted, $visited);
        }

        return $sorted;
    }

    private function sortRelease(Release $release, array $releaseMap, array &$sorted, array &$visited): void
    {
        if (isset($visited[$release->name])) {
            return;
        }

        $visited[$release->name] = true;

        // First, process dependencies
        foreach ($release->dependents as $dependentName) {
            if (isset($releaseMap[$dependentName])) {
                $this->sortRelease($releaseMap[$dependentName], $releaseMap, $sorted, $visited);
            }
        }

        $sorted[] = $release;
    }

    public function validateReleasePlan(array $releases): array
    {
        $errors = [];

        foreach ($releases as $release) {
            $package = $this->dependencyGraph->getPackage($release->name);
            if ($package === null) {
                $errors[] = "Package '{$release->name}' not found in dependency graph";
                continue;
            }

            // Validate version bump
            $currentVersion = Version::fromString($release->oldVersion);
            $newVersion = Version::fromString($release->newVersion);

            if (!$newVersion->isGreaterThan($currentVersion)) {
                $errors[] = "New version '{$release->newVersion}' must be greater than old version '{$release->oldVersion}' for package '{$release->name}'";
            }

            // Validate changesets
            if (empty($release->changesets)) {
                $errors[] = "Release for package '{$release->name}' has no changesets";
            }

            // Validate dependents
            foreach ($release->dependents as $dependentName) {
                if (!$this->dependencyGraph->hasPackage($dependentName)) {
                    $errors[] = "Dependent package '{$dependentName}' not found for package '{$release->name}'";
                }
            }
        }

        return $errors;
    }

    public function getReleaseSummary(array $releases): array
    {
        $summary = [
            'totalReleases' => count($releases),
            'packages' => [],
            'changesets' => [],
            'versionTypes' => [
                'major' => 0,
                'minor' => 0,
                'patch' => 0,
            ],
        ];

        foreach ($releases as $release) {
            $summary['packages'][] = $release->name;
            $summary['changesets'] = array_merge($summary['changesets'], $release->changesets);
            $summary['versionTypes'][$release->type]++;
        }

        $summary['changesets'] = array_unique($summary['changesets']);
        $summary['changesetCount'] = count($summary['changesets']);

        return $summary;
    }
}
