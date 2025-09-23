<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types;

use JsonSerializable;

final class Config implements JsonSerializable
{
    /**
     * @param array{0: string, 1: array<string, mixed>} $changelog
     * @param array<string> $ignore
     * @param array{version: bool, tag: bool} $privatePackages
     */
    public function __construct(
        public readonly array $changelog,
        public readonly bool $commit = false,
        public readonly AccessType $access = AccessType::PUBLIC,
        public readonly string $baseBranch = 'main',
        public readonly string $updateInternalDependencies = 'patch',
        public readonly array $ignore = [],
        public readonly bool $prettier = true,
        public readonly array $privatePackages = ['version' => true, 'tag' => false]
    ) {
    }

    public function getChangelogGenerator(): string
    {
        return $this->changelog[0];
    }

    public function getChangelogOptions(): array
    {
        return $this->changelog[1] ?? [];
    }

    public function shouldCommit(): bool
    {
        return $this->commit;
    }

    public function isPublicAccess(): bool
    {
        return $this->access === AccessType::PUBLIC;
    }

    public function isRestrictedAccess(): bool
    {
        return $this->access === AccessType::RESTRICTED;
    }

    public function getUpdateInternalDependenciesType(): VersionType
    {
        return VersionType::from($this->updateInternalDependencies);
    }

    public function shouldIgnore(string $packageName): bool
    {
        return in_array($packageName, $this->ignore, true);
    }

    public function shouldUsePrettier(): bool
    {
        return $this->prettier;
    }

    public function shouldVersionPrivatePackages(): bool
    {
        return $this->privatePackages['version'] ?? true;
    }

    public function shouldTagPrivatePackages(): bool
    {
        return $this->privatePackages['tag'] ?? false;
    }

    public function withChangelog(string $generator, array $options = []): self
    {
        return new self(
            [$generator, $options],
            $this->commit,
            $this->access,
            $this->baseBranch,
            $this->updateInternalDependencies,
            $this->ignore,
            $this->prettier,
            $this->privatePackages
        );
    }

    public function withCommit(bool $commit): self
    {
        return new self(
            $this->changelog,
            $commit,
            $this->access,
            $this->baseBranch,
            $this->updateInternalDependencies,
            $this->ignore,
            $this->prettier,
            $this->privatePackages
        );
    }

    public function withAccess(AccessType $access): self
    {
        return new self(
            $this->changelog,
            $this->commit,
            $access,
            $this->baseBranch,
            $this->updateInternalDependencies,
            $this->ignore,
            $this->prettier,
            $this->privatePackages
        );
    }

    public function withBaseBranch(string $baseBranch): self
    {
        return new self(
            $this->changelog,
            $this->commit,
            $this->access,
            $baseBranch,
            $this->updateInternalDependencies,
            $this->ignore,
            $this->prettier,
            $this->privatePackages
        );
    }

    public function withUpdateInternalDependencies(string $updateType): self
    {
        return new self(
            $this->changelog,
            $this->commit,
            $this->access,
            $this->baseBranch,
            $updateType,
            $this->ignore,
            $this->prettier,
            $this->privatePackages
        );
    }

    public function withIgnore(array $ignore): self
    {
        return new self(
            $this->changelog,
            $this->commit,
            $this->access,
            $this->baseBranch,
            $this->updateInternalDependencies,
            $ignore,
            $this->prettier,
            $this->privatePackages
        );
    }

    public function withPrettier(bool $prettier): self
    {
        return new self(
            $this->changelog,
            $this->commit,
            $this->access,
            $this->baseBranch,
            $this->updateInternalDependencies,
            $this->ignore,
            $prettier,
            $this->privatePackages
        );
    }

    public function withPrivatePackages(array $privatePackages): self
    {
        return new self(
            $this->changelog,
            $this->commit,
            $this->access,
            $this->baseBranch,
            $this->updateInternalDependencies,
            $this->ignore,
            $this->prettier,
            $privatePackages
        );
    }

    public static function default(): self
    {
        return new self(
            changelog: ['lacus/changesets-changelog-git', ['repo' => 'user/repo']],
            commit: false,
            access: AccessType::PUBLIC,
            baseBranch: 'main',
            updateInternalDependencies: 'patch',
            ignore: [],
            prettier: true,
            privatePackages: ['version' => true, 'tag' => false]
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'changelog' => $this->changelog,
            'commit' => $this->commit,
            'access' => $this->access->value,
            'baseBranch' => $this->baseBranch,
            'updateInternalDependencies' => $this->updateInternalDependencies,
            'ignore' => $this->ignore,
            'prettier' => $this->prettier,
            'privatePackages' => $this->privatePackages,
        ];
    }
}
