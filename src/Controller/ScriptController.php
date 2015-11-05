<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Controller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Silktide\LazyBoy\Exception\InstallationException;
use Composer\Package\PackageInterface;

/**
 *
 */
class ScriptController implements PluginInterface, EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => ['install'],
            ScriptEvents::POST_UPDATE_CMD => ['install'],
        ];
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        // Move along, nothing to see here
    }


    public static function install(Event $event)
    {
        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $extra = $package->getExtra();
        if (!empty($extra["lazy-boy"]["prevent-install"])) {
            return;
        }

        // template dir
        $templateDir = realpath(__DIR__ . "/../templates");
        // get app dir
        $appDir = realpath($composer->getConfig()->get("vendor-dir") . "/../");

        // check for puzzle-di
        $puzzleConfigUseStatement = "";
        $puzzleConfigLoadFiles = "";
        $dependencies = $package->getRequires();
        if (!empty($dependencies["downsider/puzzle-di"])) {
            // get package namespace
            $autoload = $package->getAutoload();
            if (!empty($autoload["psr-4"])) {
                $style = "psr-4";
            } elseif (!empty($autoload["psr-0"])) {
                $style = "psr-0";
            } else {
                throw new InstallationException("LazyBoy requires your module to use psr-4 or psr-0 autoloading");
            }
            $namespace = array_keys($autoload[$style])[0];

            $puzzleConfigUseStatement = "use {$namespace}PuzzleConfig;";
            $puzzleConfigLoadFiles =
                '$puzzleConfigs = PuzzleConfig::getConfigPaths("silktide/syringe");' . "\n" .
                '$builder->addConfigFiles($puzzleConfigs);';
        }

        $templates = [
            "routes" => [
                $templateDir . "/app/config/routes.json.temp",
                [],
                [$appDir . "/app/config/routes.json", $appDir . "/app/config/routes.yaml"]
            ],
            "services" => [
                $templateDir . "/app/config/services.json.temp",
                [],
                [$appDir . "/app/config/services.json", $appDir . "/app/config/services.yaml"]
            ],
            "bootstrap" => [
                $templateDir . "/app/bootstrap.php.temp",
                [
                    "puzzleConfigUseStatement" => $puzzleConfigUseStatement,
                    "puzzleConfigLoadFiles" => $puzzleConfigLoadFiles
                ],
                [$appDir . "/app/bootstrap.php"]
            ],

            "index" => [
                $templateDir . "/web/index.php.temp",
                [],
                [$appDir . "/web/index.php"]
            ],
            "htaccess" => [
                $templateDir . "/web/.htaccess.temp",
                [],
                [$appDir . "/web/.htaccess"]
            ]
        ];

        // see if the symfony console is installed
        $repo = $composer->getRepositoryManager()->getLocalRepository();
        // loop through the packages and check the package name
        $packages = $repo->getPackages();
        foreach ($packages as $package) {
            /** @var PackageInterface $package */
            if ($package->getName() == "symfony/console") {
                // add the console to the template list
                $templates["console"] = [
                    $templateDir . "/app/console.php.temp",
                    [],
                    [$appDir . "/app/console.php"]
                ];
                break;
            }
        }

        $output = $event->getIO();
        foreach ($templates as $template) {
            static::processTemplate($template[0], $template[1], $template[2], $output);
        }
    }

    protected static function processTemplate($templateFilePath, array $replacements = [], array $outputFilePaths = [], IOInterface $output)
    {
        foreach ($outputFilePaths as $file) {
            // If any of the output file exists, DO NOT overwrite it
            if (file_exists($file)) {
                return;
            }
        }

        $outputFilePath = $outputFilePaths[0];

        if (!file_exists($templateFilePath)) {
            throw new InstallationException("The template file '$templateFilePath' does not exist");
        }

        $contents = file_get_contents($templateFilePath);

        foreach ($replacements as $search => $replacement) {
            $search = '{{' . $search . '}}';
            $contents = str_replace($search, $replacement, $contents);
        }

        // create directory structure if necessary
        if (strpos($outputFilePath, "/") !== false) {
            $dirs = explode("/", $outputFilePath);
            array_pop($dirs);
            $currentDir = "/";
            foreach ($dirs as $dir) {
                if (!is_dir($currentDir . $dir)) {
                    mkdir($currentDir . $dir, 0777);
                }
                $currentDir .= $dir . "/";
            }
        }

        file_put_contents($outputFilePath, $contents);
        $output->write("<info>LazyBoy:</info> <comment>Created file '$outputFilePath'</comment>");
    }

} 