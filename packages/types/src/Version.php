<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types;

final class Version
{
    public function __construct(
        public readonly int $major,
        public readonly int $minor,
        public readonly int $patch,
        public readonly ?string $preRelease = null,
        public readonly ?string $build = null
    ) {
    }

    public static function fromString(string $version): self
    {
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)(?:-([a-zA-Z0-9.-]+))?(?:\+([a-zA-Z0-9.-]+))?$/', $version, $matches)) {
            throw new \InvalidArgumentException("Invalid version string: {$version}");
        }

        return new self(
            major: (int) $matches[1],
            minor: (int) $matches[2],
            patch: (int) $matches[3],
            preRelease: $matches[4] ?? null,
            build: $matches[5] ?? null
        );
    }

    public function toString(): string
    {
        $version = "{$this->major}.{$this->minor}.{$this->patch}";

        if ($this->preRelease !== null) {
            $version .= "-{$this->preRelease}";
        }

        if ($this->build !== null) {
            $version .= "+{$this->build}";
        }

        return $version;
    }

    public function bump(VersionType $type): self
    {
        return match ($type) {
            VersionType::MAJOR => new self(
                major: $this->major + 1,
                minor: 0,
                patch: 0,
                preRelease: null,
                build: null
            ),
            VersionType::MINOR => new self(
                major: $this->major,
                minor: $this->minor + 1,
                patch: 0,
                preRelease: null,
                build: null
            ),
            VersionType::PATCH => new self(
                major: $this->major,
                minor: $this->minor,
                patch: $this->patch + 1,
                preRelease: null,
                build: null
            ),
            VersionType::NONE => $this,
        };
    }

    public function isPreRelease(): bool
    {
        return $this->preRelease !== null;
    }

    public function isStable(): bool
    {
        return !$this->isPreRelease();
    }

    public function compare(Version $other): int
    {
        // Compare major, minor, patch
        $comparison = $this->major <=> $other->major;
        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = $this->minor <=> $other->minor;
        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = $this->patch <=> $other->patch;
        if ($comparison !== 0) {
            return $comparison;
        }

        // Compare pre-release
        if ($this->preRelease === null && $other->preRelease === null) {
            return 0;
        }

        if ($this->preRelease === null) {
            return 1; // This is stable, other is pre-release
        }

        if ($other->preRelease === null) {
            return -1; // This is pre-release, other is stable
        }

        return strcmp($this->preRelease, $other->preRelease);
    }

    public function isGreaterThan(Version $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function isLessThan(Version $other): bool
    {
        return $this->compare($other) < 0;
    }

    public function isEqualTo(Version $other): bool
    {
        return $this->compare($other) === 0;
    }

    public function isGreaterThanOrEqualTo(Version $other): bool
    {
        return $this->compare($other) >= 0;
    }

    public function isLessThanOrEqualTo(Version $other): bool
    {
        return $this->compare($other) <= 0;
    }

    public function withPreRelease(string $preRelease): self
    {
        return new self(
            major: $this->major,
            minor: $this->minor,
            patch: $this->patch,
            preRelease: $preRelease,
            build: $this->build
        );
    }

    public function withBuild(string $build): self
    {
        return new self(
            major: $this->major,
            minor: $this->minor,
            patch: $this->patch,
            preRelease: $this->preRelease,
            build: $build
        );
    }

    public function withoutPreRelease(): self
    {
        return new self(
            major: $this->major,
            minor: $this->minor,
            patch: $this->patch,
            preRelease: null,
            build: $this->build
        );
    }

    public function withoutBuild(): self
    {
        return new self(
            major: $this->major,
            minor: $this->minor,
            patch: $this->patch,
            preRelease: $this->preRelease,
            build: null
        );
    }
}
