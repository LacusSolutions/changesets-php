<?php

declare(strict_types=1);

namespace Lacus\Changesets\Types\Tests;

use Lacus\Changesets\Types\Version;
use Lacus\Changesets\Types\VersionType;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testFromString(): void
    {
        $version = Version::fromString('1.2.3');
        $this->assertEquals(1, $version->major);
        $this->assertEquals(2, $version->minor);
        $this->assertEquals(3, $version->patch);
        $this->assertNull($version->preRelease);
        $this->assertNull($version->build);
    }

    public function testFromStringWithPreRelease(): void
    {
        $version = Version::fromString('1.2.3-alpha.1');
        $this->assertEquals(1, $version->major);
        $this->assertEquals(2, $version->minor);
        $this->assertEquals(3, $version->patch);
        $this->assertEquals('alpha.1', $version->preRelease);
        $this->assertNull($version->build);
    }

    public function testFromStringWithBuild(): void
    {
        $version = Version::fromString('1.2.3+build.1');
        $this->assertEquals(1, $version->major);
        $this->assertEquals(2, $version->minor);
        $this->assertEquals(3, $version->patch);
        $this->assertNull($version->preRelease);
        $this->assertEquals('build.1', $version->build);
    }

    public function testFromStringWithPreReleaseAndBuild(): void
    {
        $version = Version::fromString('1.2.3-alpha.1+build.1');
        $this->assertEquals(1, $version->major);
        $this->assertEquals(2, $version->minor);
        $this->assertEquals(3, $version->patch);
        $this->assertEquals('alpha.1', $version->preRelease);
        $this->assertEquals('build.1', $version->build);
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Version::fromString('invalid');
    }

    public function testToString(): void
    {
        $version = new Version(1, 2, 3);
        $this->assertEquals('1.2.3', $version->toString());
    }

    public function testToStringWithPreRelease(): void
    {
        $version = new Version(1, 2, 3, 'alpha.1');
        $this->assertEquals('1.2.3-alpha.1', $version->toString());
    }

    public function testToStringWithBuild(): void
    {
        $version = new Version(1, 2, 3, null, 'build.1');
        $this->assertEquals('1.2.3+build.1', $version->toString());
    }

    public function testBumpMajor(): void
    {
        $version = new Version(1, 2, 3);
        $bumped = $version->bump(VersionType::MAJOR);
        $this->assertEquals('2.0.0', $bumped->toString());
    }

    public function testBumpMinor(): void
    {
        $version = new Version(1, 2, 3);
        $bumped = $version->bump(VersionType::MINOR);
        $this->assertEquals('1.3.0', $bumped->toString());
    }

    public function testBumpPatch(): void
    {
        $version = new Version(1, 2, 3);
        $bumped = $version->bump(VersionType::PATCH);
        $this->assertEquals('1.2.4', $bumped->toString());
    }

    public function testBumpNone(): void
    {
        $version = new Version(1, 2, 3);
        $bumped = $version->bump(VersionType::NONE);
        $this->assertEquals('1.2.3', $bumped->toString());
    }

    public function testIsPreRelease(): void
    {
        $this->assertFalse((new Version(1, 2, 3))->isPreRelease());
        $this->assertTrue((new Version(1, 2, 3, 'alpha.1'))->isPreRelease());
    }

    public function testIsStable(): void
    {
        $this->assertTrue((new Version(1, 2, 3))->isStable());
        $this->assertFalse((new Version(1, 2, 3, 'alpha.1'))->isStable());
    }

    public function testCompare(): void
    {
        $v1 = new Version(1, 2, 3);
        $v2 = new Version(1, 2, 4);
        $v3 = new Version(2, 0, 0);

        $this->assertEquals(-1, $v1->compare($v2));
        $this->assertEquals(1, $v2->compare($v1));
        $this->assertEquals(0, $v1->compare($v1));
        $this->assertEquals(-1, $v1->compare($v3));
    }

    public function testCompareWithPreRelease(): void
    {
        $stable = new Version(1, 2, 3);
        $preRelease = new Version(1, 2, 3, 'alpha.1');

        $this->assertEquals(1, $stable->compare($preRelease));
        $this->assertEquals(-1, $preRelease->compare($stable));
    }

    public function testComparisonMethods(): void
    {
        $v1 = new Version(1, 2, 3);
        $v2 = new Version(1, 2, 4);

        $this->assertTrue($v2->isGreaterThan($v1));
        $this->assertTrue($v1->isLessThan($v2));
        $this->assertTrue($v1->isEqualTo($v1));
        $this->assertTrue($v2->isGreaterThanOrEqualTo($v1));
        $this->assertTrue($v1->isLessThanOrEqualTo($v2));
    }
}
