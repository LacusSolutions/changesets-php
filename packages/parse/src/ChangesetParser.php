<?php

declare(strict_types=1);

namespace Lacus\Changesets\Parse;

use Lacus\Changesets\Types\Changeset;
use Lacus\Changesets\Types\VersionType;
use Symfony\Component\Yaml\Yaml;

final class ChangesetParser
{
    public function parseChangeset(string $content): Changeset
    {
        $parts = $this->splitFrontMatter($content);
        $frontMatter = $this->parseFrontMatter($parts['frontMatter']);
        $summary = $this->parseSummary($parts['content']);

        return new Changeset(
            id: $frontMatter['id'] ?? '',
            releases: $this->parseReleases($frontMatter),
            summary: $summary,
            commit: $frontMatter['commit'] ?? null,
            linked: $frontMatter['linked'] ?? null
        );
    }

    public function parseChangesetFile(string $filePath): Changeset
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Changeset file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read changeset file: {$filePath}");
        }

        return $this->parseChangeset($content);
    }

    public function serializeChangeset(Changeset $changeset): string
    {
        $frontMatter = $this->buildFrontMatter($changeset);
        $content = $changeset->summary;

        return "---\n{$frontMatter}---\n\n{$content}\n";
    }

    public function writeChangesetFile(Changeset $changeset, string $filePath): void
    {
        $content = $this->serializeChangeset($changeset);

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Failed to write changeset file: {$filePath}");
        }
    }

    private function splitFrontMatter(string $content): array
    {
        $lines = explode("\n", $content);

        if (empty($lines) || $lines[0] !== '---') {
            throw new \InvalidArgumentException('Changeset must start with front matter (---)');
        }

        $frontMatterLines = [];
        $contentLines = [];
        $inFrontMatter = true;
        $frontMatterEnded = false;

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            if ($inFrontMatter && $line === '---') {
                $inFrontMatter = false;
                $frontMatterEnded = true;
                continue;
            }

            if ($inFrontMatter) {
                $frontMatterLines[] = $line;
            } else {
                $contentLines[] = $line;
            }
        }

        if (!$frontMatterEnded) {
            throw new \InvalidArgumentException('Changeset front matter must be closed with ---');
        }

        return [
            'frontMatter' => implode("\n", $frontMatterLines),
            'content' => implode("\n", $contentLines),
        ];
    }

    private function parseFrontMatter(string $frontMatter): array
    {
        if (empty(trim($frontMatter))) {
            return [];
        }

        try {
            return Yaml::parse($frontMatter);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid YAML in changeset front matter: " . $e->getMessage());
        }
    }

    private function parseSummary(string $content): string
    {
        return trim($content);
    }

    private function parseReleases(array $frontMatter): array
    {
        $releases = [];

        foreach ($frontMatter as $key => $value) {
            if ($key === 'id' || $key === 'commit' || $key === 'linked') {
                continue;
            }

            if (is_string($value)) {
                try {
                    $releases[$key] = VersionType::from($value);
                } catch (\ValueError $e) {
                    throw new \InvalidArgumentException("Invalid version type '{$value}' for package '{$key}'");
                }
            }
        }

        return $releases;
    }

    private function buildFrontMatter(Changeset $changeset): string
    {
        $data = [];

        if (!empty($changeset->id)) {
            $data['id'] = $changeset->id;
        }

        foreach ($changeset->releases as $package => $versionType) {
            $data[$package] = $versionType->value;
        }

        if ($changeset->commit !== null) {
            $data['commit'] = $changeset->commit;
        }

        if ($changeset->linked !== null) {
            $data['linked'] = $changeset->linked;
        }

        return Yaml::dump($data, 2, 2);
    }

    public function validateChangeset(Changeset $changeset): array
    {
        $errors = [];

        if (empty($changeset->id)) {
            $errors[] = 'Changeset ID is required';
        }

        if (empty($changeset->releases)) {
            $errors[] = 'At least one package release is required';
        }

        if (empty(trim($changeset->summary))) {
            $errors[] = 'Changeset summary is required';
        }

        foreach ($changeset->releases as $package => $versionType) {
            if (empty($package)) {
                $errors[] = 'Package name cannot be empty';
            }

            if (!str_contains($package, '/')) {
                $errors[] = "Package name '{$package}' must be in format 'vendor/package'";
            }
        }

        return $errors;
    }

    public function isValidChangeset(Changeset $changeset): bool
    {
        return empty($this->validateChangeset($changeset));
    }
}
