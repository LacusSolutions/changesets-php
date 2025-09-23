<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types;

use JsonSerializable;

final class Changeset implements JsonSerializable
{
    /**
     * @param array<string, VersionType> $releases
     */
    public function __construct(
        public readonly string $id,
        public readonly array $releases,
        public readonly string $summary,
        public readonly ?string $commit = null,
        public readonly ?string $linked = null
    ) {
    }

    public function getReleaseForPackage(string $packageName): ?VersionType
    {
        return $this->releases[$packageName] ?? null;
    }

    public function hasReleaseForPackage(string $packageName): bool
    {
        return isset($this->releases[$packageName]);
    }

    public function getPackages(): array
    {
        return array_keys($this->releases);
    }

    public function isEmpty(): bool
    {
        return empty($this->releases);
    }

    public function withRelease(string $packageName, VersionType $versionType): self
    {
        $releases = $this->releases;
        $releases[$packageName] = $versionType;

        return new self(
            $this->id,
            $releases,
            $this->summary,
            $this->commit,
            $this->linked
        );
    }

    public function withoutRelease(string $packageName): self
    {
        $releases = $this->releases;
        unset($releases[$packageName]);

        return new self(
            $this->id,
            $releases,
            $this->summary,
            $this->commit,
            $this->linked
        );
    }

    public function withSummary(string $summary): self
    {
        return new self(
            $this->id,
            $this->releases,
            $summary,
            $this->commit,
            $this->linked
        );
    }

    public function withCommit(string $commit): self
    {
        return new self(
            $this->id,
            $this->releases,
            $this->summary,
            $commit,
            $this->linked
        );
    }

    public function withLinked(string $linked): self
    {
        return new self(
            $this->id,
            $this->releases,
            $this->summary,
            $this->commit,
            $linked
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'releases' => array_map(
                fn(VersionType $type) => $type->value,
                $this->releases
            ),
            'summary' => $this->summary,
            'commit' => $this->commit,
            'linked' => $this->linked,
        ];
    }
}
