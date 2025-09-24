<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types\Tests;

use Lacus\Changesets\Types\VersionType;
use PHPUnit\Framework\TestCase;

final class VersionTypeTest extends TestCase
{
    public function testGetBumpValue(): void
    {
        $this->assertEquals(3, VersionType::MAJOR->getBumpValue());
        $this->assertEquals(2, VersionType::MINOR->getBumpValue());
        $this->assertEquals(1, VersionType::PATCH->getBumpValue());
        $this->assertEquals(0, VersionType::NONE->getBumpValue());
    }

    public function testIsBreaking(): void
    {
        $this->assertTrue(VersionType::MAJOR->isBreaking());
        $this->assertFalse(VersionType::MINOR->isBreaking());
        $this->assertFalse(VersionType::PATCH->isBreaking());
        $this->assertFalse(VersionType::NONE->isBreaking());
    }

    public function testIsFeature(): void
    {
        $this->assertFalse(VersionType::MAJOR->isFeature());
        $this->assertTrue(VersionType::MINOR->isFeature());
        $this->assertFalse(VersionType::PATCH->isFeature());
        $this->assertFalse(VersionType::NONE->isFeature());
    }

    public function testIsFix(): void
    {
        $this->assertFalse(VersionType::MAJOR->isFix());
        $this->assertFalse(VersionType::MINOR->isFix());
        $this->assertTrue(VersionType::PATCH->isFix());
        $this->assertFalse(VersionType::NONE->isFix());
    }

    public function testIsNone(): void
    {
        $this->assertFalse(VersionType::MAJOR->isNone());
        $this->assertFalse(VersionType::MINOR->isNone());
        $this->assertFalse(VersionType::PATCH->isNone());
        $this->assertTrue(VersionType::NONE->isNone());
    }
}
