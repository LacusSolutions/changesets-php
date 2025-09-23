<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types;

use JsonSerializable;

final class Package implements JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $directory,
        public readonly bool $private = false,
        public readonly array $dependencies = [],
        public readonly array $devDependencies = [],
        public readonly array $peerDependencies = []
    ) {
    }

    public function getDependencyVersion(string $dependencyName): ?string
    {
        return $this->dependencies[$dependencyName] ?? null;
    }

    public function getDevDependencyVersion(string $dependencyName): ?string
    {
        return $this->devDependencies[$dependencyName] ?? null;
    }

    public function getPeerDependencyVersion(string $dependencyName): ?string
    {
        return $this->peerDependencies[$dependencyName] ?? null;
    }

    public function hasDependency(string $dependencyName): bool
    {
        return isset($this->dependencies[$dependencyName]);
    }

    public function hasDevDependency(string $dependencyName): bool
    {
        return isset($this->devDependencies[$dependencyName]);
    }

    public function hasPeerDependency(string $dependencyName): bool
    {
        return isset($this->peerDependencies[$dependencyName]);
    }

    public function isInternalPackage(): bool
    {
        return str_starts_with($this->name, 'lacus/');
    }

    public function withVersion(string $version): self
    {
        return new self(
            $this->name,
            $version,
            $this->directory,
            $this->private,
            $this->dependencies,
            $this->devDependencies,
            $this->peerDependencies
        );
    }

    public function withDependencies(array $dependencies): self
    {
        return new self(
            $this->name,
            $this->version,
            $this->directory,
            $this->private,
            $dependencies,
            $this->devDependencies,
            $this->peerDependencies
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'directory' => $this->directory,
            'private' => $this->private,
            'dependencies' => $this->dependencies,
            'devDependencies' => $this->devDependencies,
            'peerDependencies' => $this->peerDependencies,
        ];
    }
}
