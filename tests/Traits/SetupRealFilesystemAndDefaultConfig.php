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

    /**
     * Mock object for getcwd.
     *
     * @var Mock
     */
    protected $mock;

    public function setUp()
    {
        $currentDirectory = getcwd();
        $_SERVER['HOME'] = vfsStream::url($this->folderLocation);
        $this->configurationLocation = $this->folderLocation . '/.config/boiler';

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory($this->folderLocation));
        $this->config = Configuration::getInstance();

        $pathToAdd = $currentDirectory . '/tests/tmp/' . $this->pathToAdd;
        mkdir($pathToAdd, 0777, true);
        $this->pathToAdd = $pathToAdd;

        $pathToRemove = $currentDirectory . '/tests/tmp/' . $this->pathToRemove;
        mkdir($pathToRemove, 0777, true);
        $this->pathToRemove = $pathToRemove;

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
        $finder->in(getcwd().'/tests/tmp')->directories()->sortByType()->reverseSorting();

        foreach ($finder as $file) {
            rmdir($file->getPathname());
        }

        rmdir(getcwd().'/tests/tmp');
    }
}
