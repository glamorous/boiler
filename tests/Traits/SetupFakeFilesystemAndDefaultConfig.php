<?php

namespace Glamorous\Boiler\Tests\Traits;

use Glamorous\Boiler\Helpers\Configuration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

trait SetupFakeFilesystemAndDefaultConfig
{
    /**
     * Location of the VFS folder.
     *
     * @var string
     */
    private $folderLocation = 'tmp';

    /**
     * Configuration location.
     *
     * @var string
     */
    private $configurationLocation;

    /**
     * Configuration object.
     *
     * @var Configuration
     */
    private $config;

    /**
     * Path to add.
     *
     * @var string
     */
    private $pathToAdd = 'path/to/add';

    /**
     * Path to remove.
     *
     * @var string
     */
    private $pathToRemove = 'path/to/remove';

    public function setUp()
    {
        $_SERVER['HOME'] = vfsStream::url($this->folderLocation);
        $this->configurationLocation = $this->folderLocation . '/.config/boiler';

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory($this->folderLocation));
        $this->config = Configuration::getInstance();

        $pathToAdd = vfsStream::url($this->folderLocation) . '/' . $this->pathToAdd;
        mkdir($pathToAdd, 0777, true);
        $this->pathToAdd = $pathToAdd;

        $pathToRemove = vfsStream::url($this->folderLocation) . '/' . $this->pathToRemove;
        mkdir($pathToRemove, 0777, true);
        $this->pathToRemove = $pathToRemove;
    }
}
