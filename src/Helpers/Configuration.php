<?php

namespace Glamorous\Boiler\Helpers;

class Configuration
{
    /**
     * Array with the whole configuration.
     *
     * @var array
     */
    private $config = [];

    /**
     * Location of the config file.
     *
     * @var string
     */
    private $configLocation = '/.config/boiler';

    /**
     * Filename of the config file.
     *
     * @var string
     */
    private $configFile = 'configuration.json';

    /**
     * Full path to the configurationFile.
     *
     * @var string
     */
    private $configFileLocation;

    /**
     * Singleton pattern instance.
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Configuration constructor.
     *
     * @SuppressWarnings(Superglobals)
     */
    private function __construct()
    {
        $this->configLocation = $_SERVER['HOME'] . $this->configLocation;
        $this->configFileLocation = $this->configLocation . '/' . $this->configFile;
        $this->setupConfiguration();
    }

    /**
     * Get singleton instance of the class.
     *
     * @return Configuration
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Configuration();
        }

        return self::$instance;
    }

    private function setupConfiguration()
    {
        if (!is_dir($this->configLocation)) {
            mkdir($this->configLocation, 0777, true);
        }

        if (!file_exists($this->configFileLocation)) {
            $this->config = [
                'paths' => [],
            ];
            $this->saveConfigurationFile();
        }

        $this->loadConfiguration();
    }

    public function loadConfiguration()
    {
        $json = file_get_contents($this->configFileLocation);
        $this->config = $json ? json_decode($json, true) : [];
    }

    private function saveConfigurationFile()
    {
        file_put_contents(
            $this->configFileLocation,
            json_encode($this->config, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );
    }

    /**
     * Add the current path to the configuration.
     *
     * @param string $path
     *
     * @return bool
     */
    public function addPath(string $path): bool
    {
        if (!is_array($this->config['paths']) || !in_array($path, $this->config['paths'])) {
            $this->config['paths'][] = $path;
            $this->saveConfigurationFile();

            return true;
        }

        return false;
    }

    /**
     * Remove the given path from the configuration.
     *
     * @param string $path
     *
     * @return bool
     */
    public function removePath(string $path): bool
    {
        $arrayKey = array_search($path, $this->config['paths']);
        if (is_array($this->config['paths']) && $arrayKey !== false) {
            unset($this->config['paths'][$arrayKey]);
            $this->saveConfigurationFile();

            return true;
        }

        return false;
    }

    /**
     * Get all paths from the configuration.
     *
     * @return array
     */
    public function getPaths(): array
    {
        if (array_key_exists('paths', $this->config) && is_array($this->config['paths'])) {
            return $this->config['paths'];
        }

        return [];
    }
}
