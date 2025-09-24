<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types;

enum VersionType: string
{
    case MAJOR = 'major';
    case MINOR = 'minor';
    case PATCH = 'patch';
    case NONE = 'none';

    public function getBumpValue(): int
    {
        return match ($this) {
            self::MAJOR => 3,
            self::MINOR => 2,
            self::PATCH => 1,
            self::NONE => 0,
        };
    }

    public function isBreaking(): bool
    {
        return $this === self::MAJOR;
    }

    public function isFeature(): bool
    {
        return $this === self::MINOR;
    }

    public function isFix(): bool
    {
        return $this === self::PATCH;
    }

    public function isNone(): bool
    {
        return $this === self::NONE;
    }
}
