<?php


namespace Glamorous\Boiler\Helpers;

use Glamorous\Boiler\BoilerException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Template
{
    /**
     * Singleton pattern instance.
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Array with the paths.
     *
     * @var array
     */
    protected $paths;

    /**
     * Template constructor.
     */
    private function __construct()
    {
    }

    /**
     * Get singleton instance of the class.
     *
     * @return Template
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Template();
        }

        return self::$instance;
    }

    /**
     * Method to get a valid template from a given template name.
     *
     * @param string $fileName
     * @param array $paths
     *
     * @return array
     *
     * @throws BoilerException
     */
    public function searchTemplateByFilenameInGivenPaths(string $fileName, array $paths): array
    {
        $this->paths = $paths;
        $template = $this->findAndParseTemplate($fileName);
        $extraTemplates = $this->getIncludedTemplates($template);
        $template = $this->mergeExtraTemplates($template, $extraTemplates);

        $this->validateTemplate($template);

        return $template;
    }

    /**
     * Find and parse template file.
     *
     * @param string $templateFileName
     *
     * @return array
     *
     * @throws BoilerException
     */
    protected function findAndParseTemplate(string $templateFileName): array
    {
        $templateFile = $this->getTemplate($templateFileName);

        if (is_null($templateFile)) {
            throw new BoilerException('No template found with name ' . $templateFileName);
        }

        $pathName = $templateFile->getPathname();
        $template = $this->parseTemplateFile($pathName);

        if (!is_array($template)) {
            throw new BoilerException('Template could not be parsed as a yaml file');
        }

        return $template;
    }

    /**
     * Get the template.
     *
     * @param string $name
     *
     * @return null|SplFileInfo
     */
    protected function getTemplate(string $name): ?SplFileInfo
    {
        foreach ($this->paths as $directory) {
            $fileInDirectory = $this->findYamlFileInDirectory($name, $directory . '/' . $name);

            if (!is_null($fileInDirectory)) {
                return $fileInDirectory;
            }

            $file = $this->findYamlFileInDirectory($name, $directory);

            if (!is_null($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Parse and load yml file from path.
     *
     * @param string $path
     *
     * @return mixed
     *
     * @throws BoilerException
     */
    protected function parseTemplateFile(string $path)
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new BoilerException(
                'Template file located at `' . $path . '` could not be parsed',
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Find a yaml file with correct name in a specified directory.
     *
     * @param string $name
     * @param string $directory
     *
     * @return null|SplFileInfo
     */
    protected function findYamlFileInDirectory(string $name, string $directory)
    {
        if (!is_dir($directory)) {
            return null;
        }

        $name .= '.yml';
        $finder = new Finder();
        $finder->files()->name($name)->depth('== 0')->in($directory);

        foreach ($finder as $file) {
            if ($file->getFilename() === $name) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Load included templates.
     *
     * @param array $template
     *
     * @return array
     * @throws BoilerException
     */
    protected function getIncludedTemplates(array $template): array
    {
        if (!array_key_exists('include', $template)) {
            return [];
        }

        if (!is_array($template['include'])) {
            throw new BoilerException('Include function must be an array.');
        }

        return array_map(function ($fileName) {
            $templateFile = $this->getTemplate($fileName);
            if ($templateFile === null) {
                throw new BoilerException('Included file `' . $fileName . '` does not exists');
            }
            $template = $this->parseTemplateFile($templateFile->getPathname());

            if (!$template) {
                throw new BoilerException('Included file `' . $fileName . '` cannot be parsed');
            }

            return $template;
        }, $template['include']);
    }

    /**
     * Merge the extra template (if there are) with the main template.
     *
     * @param array $template
     * @param array $extraTemplates
     *
     * @return array
     */
    protected function mergeExtraTemplates(array $template, array $extraTemplates): array
    {
        foreach ($extraTemplates as $extraTemplate) {
            $template = array_merge($template, $extraTemplate);
        }

        return $template;
    }

    /**
     * Validate the loaded template-file.
     *
     * @param array $template
     *
     * @throws BoilerException
     */
    protected function validateTemplate(array $template): void
    {
        if (!array_key_exists('name', $template) || empty($template['name'])) {
            throw new BoilerException('There\'s no name defined in the template');
        }

        if (!array_key_exists('steps', $template) || !is_array($template['steps']) || empty($template['steps'])) {
            throw new BoilerException('There are no steps defined in the template');
        }

        $this->validateSteps($template);
    }

    /**
     * Internal method to validate the steps.
     *
     * @param array $template
     *
     * @throws BoilerException
     */
    protected function validateSteps(array $template): void
    {
        $steps = $template['steps'];
        foreach ($steps as $step) {
            if (!array_key_exists($step, $template)) {
                throw new BoilerException('The step `' . $step . '` does not exists.');
            }

            if (!array_key_exists('name', $template[$step])) {
                throw new BoilerException('No `name` set for the step `' . $step . '`');
            }

            if (!array_key_exists('script', $template[$step])) {
                throw new BoilerException('No `script` set for the step `' . $step . '`');
            }
        }
    }
}
