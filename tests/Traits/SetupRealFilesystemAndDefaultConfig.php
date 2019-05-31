<?php

namespace Glamorous\Boiler\Tests\Traits;

use Glamorous\Boiler\Configuration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use phpmock\Mock;
use phpmock\MockBuilder;
use Symfony\Component\Finder\Finder;

trait SetupRealFilesystemAndDefaultConfig
{
    use ConfigurationTearDown {
        tearDown as configurationTearDown;
    }

    /**
     * Location of the test folder.
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
     * Current Directory.
     *
     * @var string
     */
    private $currentDirectory;

    /**
     * Path to add.
     *
     * @var string
     */
    private $pathToAdd;

    /**
     * Path to remove.
     *
     * @var string
     */
    private $pathToRemove;

    /**
     * Path to run command.
     *
     * @var string
     */
    private $pathToRunCommand;

    /**
     * Mock object for getcwd.
     *
     * @var Mock
     */
    protected $mock;

    public function setUp()
    {
        $this->currentDirectory = getcwd();

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory($this->folderLocation));
        $this->config = Configuration::getInstance();

        $pathToAdd = $this->currentDirectory . '/tests/tmp/path/to/add';
        mkdir($pathToAdd, 0777, true);
        $this->pathToAdd = $pathToAdd;

        $pathToRemove = $this->currentDirectory . '/tests/tmp/path/to/remove';
        mkdir($pathToRemove, 0777, true);
        $this->pathToRemove = $pathToRemove;

        $this->pathToRunCommand = $this->currentDirectory . '/tests/tmp';

        $builder = new MockBuilder();
        $builder->setNamespace('Glamorous\Boiler')
            ->setName('getcwd')
            ->setFunction(
                function () {
                    return false;
                }
            );

        $this->mock = $builder->build();
        $this->mock->define();
        Mock::disableAll();
    }

    public function tearDown()
    {
        $this->configurationTearDown();

        $finder = new Finder();
        $finder->in($this->currentDirectory . '/tests/tmp')->sortByType()->reverseSorting();

        foreach ($finder as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
                continue;
            }
            rmdir($file->getPathname());
        }

        rmdir($this->currentDirectory . '/tests/tmp');
    }
}
