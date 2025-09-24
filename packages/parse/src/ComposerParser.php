<?php

declare(strict_types=1);

namespace Lacus\Changesets\Parse;

use Lacus\Changesets\Types\Package;

final class ComposerParser
{
    public function parseComposerJson(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Composer file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read composer file: {$filePath}");
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Invalid JSON in composer file: " . json_last_error_msg());
        }

        return $data;
    }

    public function parsePackage(string $composerJsonPath): Package
    {
        $data = $this->parseComposerJson($composerJsonPath);
        $directory = dirname($composerJsonPath);

        $name = $data['name'] ?? '';
        if (empty($name)) {
            throw new \InvalidArgumentException("Package name is required in composer.json");
        }

        $version = $data['version'] ?? '0.0.0';
        $private = $data['private'] ?? false;
        $dependencies = $data['require'] ?? [];
        $devDependencies = $data['require-dev'] ?? [];
        $peerDependencies = $data['peer-dependencies'] ?? [];

        return new Package(
            name: $name,
            version: $version,
            directory: $directory,
            private: $private,
            dependencies: $dependencies,
            devDependencies: $devDependencies,
            peerDependencies: $peerDependencies
        );
    }

    public function updatePackageVersion(string $composerJsonPath, string $newVersion): void
    {
        $data = $this->parseComposerJson($composerJsonPath);
        $data['version'] = $newVersion;

        $this->writeComposerJson($composerJsonPath, $data);
    }

    public function updateDependencyVersion(string $composerJsonPath, string $dependencyName, string $newVersion): void
    {
        $data = $this->parseComposerJson($composerJsonPath);

        if (isset($data['require'][$dependencyName])) {
            $data['require'][$dependencyName] = $newVersion;
        }

        if (isset($data['require-dev'][$dependencyName])) {
            $data['require-dev'][$dependencyName] = $newVersion;
        }

        $this->writeComposerJson($composerJsonPath, $data);
    }

    public function addDependency(string $composerJsonPath, string $dependencyName, string $version, bool $dev = false): void
    {
        $data = $this->parseComposerJson($composerJsonPath);

        $key = $dev ? 'require-dev' : 'require';
        if (!isset($data[$key])) {
            $data[$key] = [];
        }

        $data[$key][$dependencyName] = $version;

        $this->writeComposerJson($composerJsonPath, $data);
    }

    public function removeDependency(string $composerJsonPath, string $dependencyName): void
    {
        $data = $this->parseComposerJson($composerJsonPath);

        unset($data['require'][$dependencyName]);
        unset($data['require-dev'][$dependencyName]);

        $this->writeComposerJson($composerJsonPath, $data);
    }

    public function hasDependency(string $composerJsonPath, string $dependencyName): bool
    {
        $data = $this->parseComposerJson($composerJsonPath);

        return isset($data['require'][$dependencyName]) || isset($data['require-dev'][$dependencyName]);
    }

    public function getDependencyVersion(string $composerJsonPath, string $dependencyName): ?string
    {
        $data = $this->parseComposerJson($composerJsonPath);

        return $data['require'][$dependencyName] ?? $data['require-dev'][$dependencyName] ?? null;
    }

    public function isPrivatePackage(string $composerJsonPath): bool
    {
        $data = $this->parseComposerJson($composerJsonPath);
        return $data['private'] ?? false;
    }

    public function getPackageName(string $composerJsonPath): string
    {
        $data = $this->parseComposerJson($composerJsonPath);
        return $data['name'] ?? '';
    }

    public function getPackageVersion(string $composerJsonPath): string
    {
        $data = $this->parseComposerJson($composerJsonPath);
        return $data['version'] ?? '0.0.0';
    }

    public function getWorkspacePackages(string $rootComposerJsonPath): array
    {
        $data = $this->parseComposerJson($rootComposerJsonPath);
        $workspaces = $data['workspaces'] ?? [];

        if (empty($workspaces)) {
            return [];
        }

        $packages = [];
        $rootDir = dirname($rootComposerJsonPath);

        foreach ($workspaces as $workspace) {
            $pattern = $rootDir . '/' . $workspace;
            $directories = glob($pattern, GLOB_ONLYDIR);

            foreach ($directories as $directory) {
                $composerJsonPath = $directory . '/composer.json';
                if (file_exists($composerJsonPath)) {
                    try {
                        $packages[] = $this->parsePackage($composerJsonPath);
                    } catch (\Exception $e) {
                        // Skip invalid packages
                        continue;
                    }
                }
            }
        }

        return $packages;
    }

    public function findComposerFiles(string $rootDir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getFilename() === 'composer.json') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function writeComposerJson(string $filePath, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException("Failed to encode composer data as JSON");
        }

        if (file_put_contents($filePath, $json) === false) {
            throw new \RuntimeException("Failed to write composer file: {$filePath}");
        }
    }
}
