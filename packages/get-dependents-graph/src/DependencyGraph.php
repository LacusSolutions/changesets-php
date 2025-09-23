<?php

declare(strict_types=1);

namespace Lacus\Changesets\GetDependentsGraph;

use Lacus\Changesets\Parse\ComposerParser;
use Lacus\Changesets\Types\Package;

final class DependencyGraph
{
    private ComposerParser $composerParser;
    private array $packages = [];
    private array $dependencies = [];
    private array $dependents = [];

    public function __construct()
    {
        $this->composerParser = new ComposerParser();
    }

    public function addPackage(Package $package): void
    {
        $this->packages[$package->name] = $package;
        $this->dependencies[$package->name] = [];
        $this->dependents[$package->name] = [];
    }

    public function addPackages(array $packages): void
    {
        foreach ($packages as $package) {
            $this->addPackage($package);
        }
    }

    public function buildGraph(): void
    {
        $this->dependencies = [];
        $this->dependents = [];

        foreach ($this->packages as $package) {
            $this->dependencies[$package->name] = [];
            $this->dependents[$package->name] = [];
        }

        foreach ($this->packages as $package) {
            $this->processPackageDependencies($package);
        }
    }

    private function processPackageDependencies(Package $package): void
    {
        foreach ($package->dependencies as $dependencyName => $version) {
            if (isset($this->packages[$dependencyName])) {
                $this->dependencies[$package->name][$dependencyName] = $version;
                $this->dependents[$dependencyName][$package->name] = $version;
            }
        }
    }

    public function getDependencies(string $packageName): array
    {
        return $this->dependencies[$packageName] ?? [];
    }

    public function getDependents(string $packageName): array
    {
        return $this->dependents[$packageName] ?? [];
    }

    public function getDirectDependents(string $packageName): array
    {
        return array_keys($this->dependents[$packageName] ?? []);
    }

    public function getAllDependents(string $packageName): array
    {
        $allDependents = [];
        $visited = [];
        $this->collectAllDependents($packageName, $allDependents, $visited);
        return array_unique($allDependents);
    }

    private function collectAllDependents(string $packageName, array &$allDependents, array &$visited): void
    {
        if (isset($visited[$packageName])) {
            return;
        }

        $visited[$packageName] = true;
        $directDependents = $this->getDirectDependents($packageName);

        foreach ($directDependents as $dependent) {
            $allDependents[] = $dependent;
            $this->collectAllDependents($dependent, $allDependents, $visited);
        }
    }

    public function getDependencyChain(string $packageName): array
    {
        $chain = [];
        $visited = [];
        $this->buildDependencyChain($packageName, $chain, $visited);
        return array_reverse($chain);
    }

    private function buildDependencyChain(string $packageName, array &$chain, array &$visited): void
    {
        if (isset($visited[$packageName])) {
            return;
        }

        $visited[$packageName] = true;
        $dependencies = array_keys($this->dependencies[$packageName] ?? []);

        foreach ($dependencies as $dependency) {
            $this->buildDependencyChain($dependency, $chain, $visited);
        }

        $chain[] = $packageName;
    }

    public function hasCircularDependencies(): bool
    {
        $visited = [];
        $recursionStack = [];

        foreach (array_keys($this->packages) as $package) {
            if (!isset($visited[$package])) {
                if ($this->hasCircularDependency($package, $visited, $recursionStack)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasCircularDependency(string $package, array &$visited, array &$recursionStack): bool
    {
        $visited[$package] = true;
        $recursionStack[$package] = true;

        $dependencies = array_keys($this->dependencies[$package] ?? []);
        foreach ($dependencies as $dependency) {
            if (!isset($visited[$dependency])) {
                if ($this->hasCircularDependency($dependency, $visited, $recursionStack)) {
                    return true;
                }
            } elseif (isset($recursionStack[$dependency])) {
                return true;
            }
        }

        unset($recursionStack[$package]);
        return false;
    }

    public function getCircularDependencies(): array
    {
        $visited = [];
        $recursionStack = [];
        $circularDeps = [];

        foreach (array_keys($this->packages) as $package) {
            if (!isset($visited[$package])) {
                $this->findCircularDependencies($package, $visited, $recursionStack, $circularDeps);
            }
        }

        return $circularDeps;
    }

    private function findCircularDependencies(string $package, array &$visited, array &$recursionStack, array &$circularDeps): void
    {
        $visited[$package] = true;
        $recursionStack[$package] = true;

        $dependencies = array_keys($this->dependencies[$package] ?? []);
        foreach ($dependencies as $dependency) {
            if (!isset($visited[$dependency])) {
                $this->findCircularDependencies($dependency, $visited, $recursionStack, $circularDeps);
            } elseif (isset($recursionStack[$dependency])) {
                $circularDeps[] = [$package, $dependency];
            }
        }

        unset($recursionStack[$package]);
    }

    public function getTopologicalOrder(): array
    {
        $visited = [];
        $stack = [];

        foreach (array_keys($this->packages) as $package) {
            if (!isset($visited[$package])) {
                $this->topologicalSort($package, $visited, $stack);
            }
        }

        return array_reverse($stack);
    }

    private function topologicalSort(string $package, array &$visited, array &$stack): void
    {
        $visited[$package] = true;

        $dependencies = array_keys($this->dependencies[$package] ?? []);
        foreach ($dependencies as $dependency) {
            if (!isset($visited[$dependency])) {
                $this->topologicalSort($dependency, $visited, $stack);
            }
        }

        $stack[] = $package;
    }

    public function getPackages(): array
    {
        return $this->packages;
    }

    public function getPackage(string $packageName): ?Package
    {
        return $this->packages[$packageName] ?? null;
    }

    public function hasPackage(string $packageName): bool
    {
        return isset($this->packages[$packageName]);
    }

    public function getPackageCount(): int
    {
        return count($this->packages);
    }

    public function isInternalPackage(string $packageName): bool
    {
        $package = $this->getPackage($packageName);
        return $package !== null && $package->isInternalPackage();
    }

    public function getInternalPackages(): array
    {
        return array_filter($this->packages, fn(Package $package) => $package->isInternalPackage());
    }

    public function getExternalPackages(): array
    {
        return array_filter($this->packages, fn(Package $package) => !$package->isInternalPackage());
    }

    public function getDependencyCount(string $packageName): int
    {
        return count($this->dependencies[$packageName] ?? []);
    }

    public function getDependentCount(string $packageName): int
    {
        return count($this->dependents[$packageName] ?? []);
    }

    public function getGraphStats(): array
    {
        $internalPackages = $this->getInternalPackages();
        $externalPackages = $this->getExternalPackages();

        return [
            'totalPackages' => count($this->packages),
            'internalPackages' => count($internalPackages),
            'externalPackages' => count($externalPackages),
            'hasCircularDependencies' => $this->hasCircularDependencies(),
            'circularDependencyCount' => count($this->getCircularDependencies()),
        ];
    }
}
