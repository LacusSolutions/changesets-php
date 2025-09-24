<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types;

enum AccessType: string
{
    case PUBLIC = 'public';
    case RESTRICTED = 'restricted';

    public function isPublic(): bool
    {
        return $this === self::PUBLIC;
    }

    public function isRestricted(): bool
    {
        return $this === self::RESTRICTED;
    }
}
