<?php

declare(strict_types=1);

namespace Lacus\Changesets\Config\Tests;

use Lacus\Changesets\Config\ConfigLoader;
use Lacus\Changesets\Types\AccessType;
use PHPUnit\Framework\TestCase;

final class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
        $this->tempDir = sys_get_temp_dir() . '/changesets-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/.changeset', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testLoadConfigWithNoConfigFile(): void
    {
        $config = $this->loader->loadConfig($this->tempDir);

        $this->assertEquals(['lacus/changesets-changelog-git', ['repo' => 'user/repo']], $config->changelog);
        $this->assertFalse($config->commit);
        $this->assertEquals(AccessType::PUBLIC, $config->access);
        $this->assertEquals('main', $config->baseBranch);
        $this->assertEquals('patch', $config->updateInternalDependencies);
        $this->assertEquals([], $config->ignore);
        $this->assertTrue($config->prettier);
        $this->assertEquals(['version' => true, 'tag' => false], $config->privatePackages);
    }

    public function testLoadConfigWithPhpFile(): void
    {
        $configData = [
            'changelog' => ['custom/changelog-generator', ['option' => 'value']],
            'commit' => true,
            'access' => 'restricted',
            'baseBranch' => 'develop',
            'updateInternalDependencies' => 'minor',
            'ignore' => ['package1', 'package2'],
            'prettier' => false,
            'privatePackages' => ['version' => false, 'tag' => true],
        ];

        $configPath = $this->tempDir . '/.changeset/config.php';
        file_put_contents($configPath, '<?php return ' . var_export($configData, true) . ';');

        $config = $this->loader->loadConfig($this->tempDir);

        $this->assertEquals(['custom/changelog-generator', ['option' => 'value']], $config->changelog);
        $this->assertTrue($config->commit);
        $this->assertEquals(AccessType::RESTRICTED, $config->access);
        $this->assertEquals('develop', $config->baseBranch);
        $this->assertEquals('minor', $config->updateInternalDependencies);
        $this->assertEquals(['package1', 'package2'], $config->ignore);
        $this->assertFalse($config->prettier);
        $this->assertEquals(['version' => false, 'tag' => true], $config->privatePackages);
    }

    public function testLoadConfigWithYamlFile(): void
    {
        $yamlContent = <<<YAML
changelog: ['custom/changelog-generator', {option: 'value'}]
commit: true
access: 'restricted'
baseBranch: 'develop'
updateInternalDependencies: 'minor'
ignore: ['package1', 'package2']
prettier: false
privatePackages: {version: false, tag: true}
YAML;

        $configPath = $this->tempDir . '/.changeset/config.yaml';
        file_put_contents($configPath, $yamlContent);

        $config = $this->loader->loadConfig($this->tempDir);

        $this->assertEquals(['custom/changelog-generator', ['option' => 'value']], $config->changelog);
        $this->assertTrue($config->commit);
        $this->assertEquals(AccessType::RESTRICTED, $config->access);
    }

    public function testLoadConfigWithJsonFile(): void
    {
        $jsonContent = json_encode([
            'changelog' => ['custom/changelog-generator', ['option' => 'value']],
            'commit' => true,
            'access' => 'restricted',
            'baseBranch' => 'develop',
            'updateInternalDependencies' => 'minor',
            'ignore' => ['package1', 'package2'],
            'prettier' => false,
            'privatePackages' => ['version' => false, 'tag' => true],
        ], JSON_PRETTY_PRINT);

        $configPath = $this->tempDir . '/.changeset/config.json';
        file_put_contents($configPath, $jsonContent);

        $config = $this->loader->loadConfig($this->tempDir);

        $this->assertEquals(['custom/changelog-generator', ['option' => 'value']], $config->changelog);
        $this->assertTrue($config->commit);
        $this->assertEquals(AccessType::RESTRICTED, $config->access);
    }

    public function testSaveConfigAsPhp(): void
    {
        $config = $this->loader->loadConfig($this->tempDir);
        $config = $config->withCommit(true)->withAccess(AccessType::RESTRICTED);

        $this->loader->saveConfig($config, $this->tempDir, 'php');

        $this->assertFileExists($this->tempDir . '/.changeset/config.php');

        $savedConfig = $this->loader->loadConfig($this->tempDir);
        $this->assertTrue($savedConfig->commit);
        $this->assertEquals(AccessType::RESTRICTED, $savedConfig->access);
    }

    public function testSaveConfigAsYaml(): void
    {
        $config = $this->loader->loadConfig($this->tempDir);
        $config = $config->withCommit(true)->withAccess(AccessType::RESTRICTED);

        $this->loader->saveConfig($config, $this->tempDir, 'yaml');

        $this->assertFileExists($this->tempDir . '/.changeset/config.yaml');

        $savedConfig = $this->loader->loadConfig($this->tempDir);
        $this->assertTrue($savedConfig->commit);
        $this->assertEquals(AccessType::RESTRICTED, $savedConfig->access);
    }

    public function testSaveConfigAsJson(): void
    {
        $config = $this->loader->loadConfig($this->tempDir);
        $config = $config->withCommit(true)->withAccess(AccessType::RESTRICTED);

        $this->loader->saveConfig($config, $this->tempDir, 'json');

        $this->assertFileExists($this->tempDir . '/.changeset/config.json');

        $savedConfig = $this->loader->loadConfig($this->tempDir);
        $this->assertTrue($savedConfig->commit);
        $this->assertEquals(AccessType::RESTRICTED, $savedConfig->access);
    }

    public function testInvalidPhpConfig(): void
    {
        $configPath = $this->tempDir . '/.changeset/config.php';
        file_put_contents($configPath, '<?php return "not an array";');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PHP config file must return an array');
        $this->loader->loadConfig($this->tempDir);
    }

    public function testInvalidJsonConfig(): void
    {
        $configPath = $this->tempDir . '/.changeset/config.json';
        file_put_contents($configPath, 'invalid json');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON in config file');
        $this->loader->loadConfig($this->tempDir);
    }

    public function testInvalidAccessType(): void
    {
        $configPath = $this->tempDir . '/.changeset/config.php';
        file_put_contents($configPath, '<?php return ["access" => "invalid"];');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid access type');
        $this->loader->loadConfig($this->tempDir);
    }

    public function testInvalidUpdateInternalDependencies(): void
    {
        $configPath = $this->tempDir . '/.changeset/config.php';
        file_put_contents($configPath, '<?php return ["updateInternalDependencies" => "invalid"];');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid updateInternalDependencies type');
        $this->loader->loadConfig($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
