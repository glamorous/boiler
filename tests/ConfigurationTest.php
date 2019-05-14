<?php

namespace Glamorous\Boiler\Tests;

use Glamorous\Boiler\Tests\Traits\ConfigurationTearDown;
use Glamorous\Boiler\Tests\Traits\SetupFakeFilesystemAndDefaultConfig;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    use SetupFakeFilesystemAndDefaultConfig, ConfigurationTearDown;

    public function test_if_configuration_directory_is_created_when_not_exists_with_correct_permissions()
    {
        static::assertTrue(vfsStreamWrapper::getRoot()->hasChild($this->configurationLocation));
        static::assertEquals(
            0777,
            vfsStreamWrapper::getRoot()->getChild($this->configurationLocation)->getPermissions()
        );
    }

    public function test_if_configuration_file_is_created_when_not_exists()
    {
        $configFile = vfsStreamWrapper::getRoot()->getChild($this->configurationLocation . '/configuration.json');
        static::assertNotNull($configFile);
    }

    public function test_that_paths_returns_empty_array_at_start()
    {
        static::assertIsArray($this->config->getPaths());
        static::assertEmpty($this->config->getPaths());
    }

    public function test_that_paths_are_empty_when_not_available_in_configuration_file()
    {
        file_put_contents(vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json', '{}');
        $this->config->loadConfiguration();
        static::assertIsArray($this->config->getPaths());
        static::assertEmpty($this->config->getPaths());
    }

    public function test_that_paths_are_returned_when_available_in_configuration_file()
    {
        $paths = [
            'my/not-existing-path/'
        ];
        $data = [
            'paths' => $paths,
        ];
        file_put_contents(
            vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );
        $this->config->loadConfiguration();
        static::assertIsArray($this->config->getPaths());
        static::assertEquals($this->config->getPaths(), $paths);
    }

    public function test_that_a_path_can_be_added_and_returns_true()
    {
        static::assertIsArray($this->config->getPaths());
        static::assertEquals($this->config->getPaths(), []);

        $result = $this->config->addPath('my/path');

        static::assertEquals($this->config->getPaths(), ['my/path']);
        static::assertTrue($result);

        $path = [
            'paths' => [
                'my/path',
            ],
        ];

        $resultJson = file_get_contents(vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json');
        static::assertJsonStringEqualsJsonString($this->getJsonString($path), $resultJson);
    }

    public function test_that_a_path_cant_be_added_when_already_exists_and_returns_false()
    {
        $paths = [
            'my/path'
        ];
        $data = [
            'paths' => $paths,
        ];
        $startJson = json_encode($data, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
        file_put_contents(vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json', $startJson);
        $this->config->loadConfiguration();
        static::assertEquals($this->config->getPaths(), ['my/path']);

        $result = $this->config->addPath('my/path');

        static::assertEquals($this->config->getPaths(), ['my/path']);
        static::assertFalse($result);

        $resultJson = file_get_contents(vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json');
        static::assertJsonStringEqualsJsonString($startJson, $resultJson);
    }

    public function test_that_a_path_can_be_removed_and_returns_true()
    {
        $paths = [
            'my/path'
        ];
        $data = [
            'paths' => $paths,
        ];

        $startJson = json_encode($data, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
        file_put_contents(vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json', $startJson);
        $this->config->loadConfiguration();
        static::assertEquals($this->config->getPaths(), ['my/path']);

        $result = $this->config->removePath('my/path');

        static::assertEquals($this->config->getPaths(), []);
        static::assertTrue($result);

        $resultJson = file_get_contents(vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json');
        static::assertJsonStringEqualsJsonString($this->getJsonString(['paths' => []]), $resultJson);
    }

    public function test_that_a_removepath_returns_false_if_path_not_exists()
    {
        static::assertIsArray($this->config->getPaths());
        static::assertEquals($this->config->getPaths(), []);

        $result = $this->config->removePath('my/path');

        static::assertEquals($this->config->getPaths(), []);
        static::assertFalse($result);

        $resultJson = file_get_contents(vfsStream::url($this->folderLocation) . '/.config/boiler/configuration.json');
        static::assertJsonStringEqualsJsonString($this->getJsonString(['paths' => []]), $resultJson);
    }

    private function getJsonString(array $data): string
    {
        $result = json_encode($data, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

        return ($result) ?: '';
    }
}
