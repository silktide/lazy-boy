<?php

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
        $output = $event->getIO();

        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $extra = $package->getExtra();
        if (
            !empty($extra["lazy-boy"]["prevent-install"]) ||
            !empty($extra["silktide/lazy-boy"]["prevent-install"])
        ) {
            return;
        }

        $lazyBoyDir = $composer->getInstallationManager()->getInstallPath(
            $composer->getRepositoryManager()->findPackage("silktide/lazy-boy", "*")
        );

        // template dir
        $templateDir = $lazyBoyDir ."/src/templates";
        // get app dir
        $appDir = realpath($composer->getConfig()->get("vendor-dir") . "/../");

        // check for puzzle-di
        $puzzleConfigUseStatement = "";
        $puzzleConfigLoadFiles = "";
        $dependencies = $package->getRequires();
        $puzzleDiPackageName = "downsider/puzzle-di";
        if (!empty($dependencies[$puzzleDiPackageName])) {
            // find PuzzleConfig's namespace
            if (!empty($extra[$puzzleDiPackageName]["namespace"])) {
                // puzzle has specified the namespace to use
                $namespace = $extra[$puzzleDiPackageName]["namespace"];
            } else {
                // get package namespace
                $autoload = $package->getAutoload();
                if (!empty($autoload["psr-4"])) {
                    $style = "psr-4";
                } elseif (!empty($autoload["psr-0"])) {
                    $style = "psr-0";
                } else {
                    throw new InstallationException("LazyBoy requires your module to use psr-4 or psr-0 autoloading");
                }

                // use the first entry in the autoload array
                $namespace = array_keys($autoload[$style])[0];
            }

            $puzzleConfigUseStatement = "use {$namespace}PuzzleConfig;";
            $puzzleConfigLoadFiles =
                '$puzzleConfigs = PuzzleConfig::getConfigPaths("silktide/syringe");' . "\n" .
                '$builder->addConfigFiles($puzzleConfigs);';
        }

        $templates = [
            "routes" => [
                $templateDir . "/app/config/routes.yml.temp",
                [],
                [$appDir . "/app/config/routes.yml", $appDir . "/app/config/routes.json"]
            ],
            "services" => [
                $templateDir . "/app/config/services.yml.temp",
                [],
                [$appDir . "/app/config/services.yml", $appDir . "/app/config/services.json"]
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
            switch ($package->getName()) {
                case "symfony/console":
                    // add the console to the template list
                    $templates["console"] = [
                        $templateDir . "/app/console.php.temp",
                        [],
                        [$appDir . "/app/console.php"]
                    ];
                    break;

                case "silktide/doctrine-wrapper":
                    $templates["doctrine"] = [
                        $templateDir . "/cli-config.php.temp",
                        [],
                        [$appDir . "/cli-config.php"]
                    ];
                    break;
            }

            // add any templates that this package has defined
            // this will overwrite any existing template of the same name, unless it is protected
            $extra = $package->getExtra();

            $protected = ["bootstrap" => true];

            if (!empty($extra["silktide/lazy-boy"]) && is_array($extra["silktide/lazy-boy"])) {
                foreach ($extra["silktide/lazy-boy"] as $templateName => $config) {

                    // prevent protected templates being overwritten
                    if (isset($protected[$templateName])) {
                        $output->write("<info>LazyBoy:</info> <error>Package '{$package->getName()}' tried to overwrite the protected template '$templateName'</error>");
                    }

                    // validate config
                    if (empty($config["template"]) || empty($config["output"])) {
                        $output->write("<info>LazyBoy:</info> <error>Invalid config for template '$templateName' in package '{$package->getName()}'</error>");
                        continue;
                    }

                    // check the template file exists
                    $packageDir = $composer->getInstallationManager()->getInstallPath($package);
                    $templateFile = $packageDir . "/" . ltrim($config["template"], "/");

                    if (!file_exists($templateFile)) {
                        $output->write("<info>LazyBoy:</info> <error>The template file '$templateFile' in package '{$package->getName()}' does not exist</error>");
                        continue;
                    }

                    // add the template to the array
                    $templates[$templateName] = [
                        $templateFile,
                        [],
                        [$appDir . "/" . ltrim($config["output"], "/")]
                    ];
                }
            }

        }




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
