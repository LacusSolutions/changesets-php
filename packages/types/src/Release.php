<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types;

use JsonSerializable;

final class Release implements JsonSerializable
{
    /**
     * @param array<string> $changesets
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $oldVersion,
        public readonly string $newVersion,
        public readonly array $changesets,
        public readonly array $dependents = []
    ) {
    }

    public function getVersionType(): VersionType
    {
        return VersionType::from($this->type);
    }

    public function isMajor(): bool
    {
        return $this->type === VersionType::MAJOR->value;
    }

    public function isMinor(): bool
    {
        return $this->type === VersionType::MINOR->value;
    }

    public function isPatch(): bool
    {
        return $this->type === VersionType::PATCH->value;
    }

    public function hasChangesets(): bool
    {
        return !empty($this->changesets);
    }

    public function getChangesetCount(): int
    {
        return count($this->changesets);
    }

    public function hasDependents(): bool
    {
        return !empty($this->dependents);
    }

    public function getDependentCount(): int
    {
        return count($this->dependents);
    }

    public function withChangesets(array $changesets): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->oldVersion,
            $this->newVersion,
            $changesets,
            $this->dependents
        );
    }

    public function withDependents(array $dependents): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->oldVersion,
            $this->newVersion,
            $this->changesets,
            $dependents
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'oldVersion' => $this->oldVersion,
            'newVersion' => $this->newVersion,
            'changesets' => $this->changesets,
            'dependents' => $this->dependents,
        ];
    }
}
