<?php

declare(strict_types=1);

namespace Lacus\Changesets\Config;

use Lacus\Changesets\Types\AccessType;
use Lacus\Changesets\Types\Config;
use Lacus\Changesets\Types\VersionType;
use Symfony\Component\Yaml\Yaml;

final class ConfigLoader
{
    private const CONFIG_FILES = [
        '.changeset/config.php',
        '.changeset/config.yaml',
        '.changeset/config.yml',
        '.changeset/config.json',
    ];

    public function loadConfig(string $rootDir = '.'): Config
    {
        $configPath = $this->findConfigFile($rootDir);

        if ($configPath === null) {
            return Config::default();
        }

        $configData = $this->loadConfigFile($configPath);
        return $this->parseConfig($configData);
    }

    private function findConfigFile(string $rootDir): ?string
    {
        foreach (self::CONFIG_FILES as $configFile) {
            $fullPath = $rootDir . '/' . $configFile;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    private function loadConfigFile(string $configPath): array
    {
        $extension = pathinfo($configPath, PATHINFO_EXTENSION);

        return match ($extension) {
            'php' => $this->loadPhpConfig($configPath),
            'yaml', 'yml' => $this->loadYamlConfig($configPath),
            'json' => $this->loadJsonConfig($configPath),
            default => throw new \InvalidArgumentException("Unsupported config file format: {$extension}"),
        };
    }

    private function loadPhpConfig(string $configPath): array
    {
        $config = include $configPath;

        if (!is_array($config)) {
            throw new \InvalidArgumentException("PHP config file must return an array");
        }

        return $config;
    }

    private function loadYamlConfig(string $configPath): array
    {
        $content = file_get_contents($configPath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read config file: {$configPath}");
        }

        return Yaml::parse($content);
    }

    private function loadJsonConfig(string $configPath): array
    {
        $content = file_get_contents($configPath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read config file: {$configPath}");
        }

        $config = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Invalid JSON in config file: " . json_last_error_msg());
        }

        return $config;
    }

    private function parseConfig(array $configData): Config
    {
        $changelog = $configData['changelog'] ?? ['lacus/changesets-changelog-git', ['repo' => 'user/repo']];
        $commit = $configData['commit'] ?? false;
        $access = AccessType::from($configData['access'] ?? 'public');
        $baseBranch = $configData['baseBranch'] ?? 'main';
        $updateInternalDependencies = $configData['updateInternalDependencies'] ?? 'patch';
        $ignore = $configData['ignore'] ?? [];
        $prettier = $configData['prettier'] ?? true;
        $privatePackages = $configData['privatePackages'] ?? ['version' => true, 'tag' => false];

        // Validate changelog format
        if (!is_array($changelog) || count($changelog) < 1) {
            throw new \InvalidArgumentException("Changelog must be an array with at least one element");
        }

        if (!is_string($changelog[0])) {
            throw new \InvalidArgumentException("Changelog generator must be a string");
        }

        if (isset($changelog[1]) && !is_array($changelog[1])) {
            throw new \InvalidArgumentException("Changelog options must be an array");
        }

        // Validate access type
        if (!in_array($access, [AccessType::PUBLIC, AccessType::RESTRICTED], true)) {
            throw new \InvalidArgumentException("Invalid access type: {$access->value}");
        }

        // Validate update internal dependencies type
        if (!in_array($updateInternalDependencies, ['major', 'minor', 'patch'], true)) {
            throw new \InvalidArgumentException("Invalid updateInternalDependencies type: {$updateInternalDependencies}");
        }

        // Validate ignore is array of strings
        if (!is_array($ignore)) {
            throw new \InvalidArgumentException("Ignore must be an array");
        }

        foreach ($ignore as $item) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException("Ignore items must be strings");
            }
        }

        // Validate private packages
        if (!is_array($privatePackages)) {
            throw new \InvalidArgumentException("Private packages must be an array");
        }

        return new Config(
            changelog: $changelog,
            commit: $commit,
            access: $access,
            baseBranch: $baseBranch,
            updateInternalDependencies: $updateInternalDependencies,
            ignore: $ignore,
            prettier: $prettier,
            privatePackages: $privatePackages
        );
    }

    public function saveConfig(Config $config, string $rootDir = '.', string $format = 'php'): void
    {
        $configDir = $rootDir . '/.changeset';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        $configPath = $configDir . '/config.' . $format;
        $configData = $config->jsonSerialize();

        match ($format) {
            'php' => $this->savePhpConfig($configPath, $configData),
            'yaml', 'yml' => $this->saveYamlConfig($configPath, $configData),
            'json' => $this->saveJsonConfig($configPath, $configData),
            default => throw new \InvalidArgumentException("Unsupported config file format: {$format}"),
        };
    }

    private function savePhpConfig(string $configPath, array $configData): void
    {
        $content = "<?php\n\nreturn " . var_export($configData, true) . ";\n";
        file_put_contents($configPath, $content);
    }

    private function saveYamlConfig(string $configPath, array $configData): void
    {
        $content = Yaml::dump($configData, 4, 2);
        file_put_contents($configPath, $content);
    }

    private function saveJsonConfig(string $configPath, array $configData): void
    {
        $content = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($configPath, $content);
    }
}
