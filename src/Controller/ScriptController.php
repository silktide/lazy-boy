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
                "template" => $templateDir . "/app/config/routes.yml.temp",
                "replacements" => [],
                "output" => [$appDir . "/app/config/routes.yml", $appDir . "/app/config/routes.json"]
            ],
            "services" => [
                "template" => $templateDir . "/app/config/services.yml.temp",
                "replacements" => [],
                "output" => [$appDir . "/app/config/services.yml", $appDir . "/app/config/services.json"]
            ],
            "bootstrap" => [
                "template" => $templateDir . "/app/bootstrap.php.temp",
                "replacements" => [
                    "puzzleConfigUseStatement" => $puzzleConfigUseStatement,
                    "puzzleConfigLoadFiles" => $puzzleConfigLoadFiles
                ],
                "output" => $appDir . "/app/bootstrap.php"
            ],

            "index" => [
                "template" => $templateDir . "/web/index.php.temp",
                "replacements" => [],
                "output" => $appDir . "/web/index.php"
            ],
            "htaccess" => [
                "template" => $templateDir . "/web/.htaccess.temp",
                "replacements" => [],
                "output" => $appDir . "/web/.htaccess"
            ]
        ];

        // see if the symfony console is installed
        $repo = $composer->getRepositoryManager()->getLocalRepository();
        // loop through the packages and check the package name
        $packages = $repo->getPackages();

        $whiteListedPackages = !empty($extra["silktide/lazy-boy"]["whiteListedPackages"])? $extra["silktide/lazy-boy"]["whiteListedPackages"]: [];
        $whiteListedPackages = is_array($whiteListedPackages)? array_flip($whiteListedPackages): [];

        $protectedTemplates = ["bootstrap" => true];

        foreach ($packages as $package) {
            /** @var PackageInterface $package */
            $packageName = $package->getName();

            switch ($packageName) {
                case "symfony/console":
                    // add the console to the template list
                    $templates["console"] = [
                        "template" => $templateDir . "/app/console.php.temp",
                        "replacements" => [],
                        "output" => $appDir . "/app/console.php"
                    ];
                    break;

                // TODO: Deprecated usage. This should be removed when the doctrine-wrapper registers its template through composer
                case "silktide/doctrine-wrapper":
                    $templates["doctrine"] = [
                        "template" => $templateDir . "/cli-config.php.temp",
                        "replacements" => [],
                        "output" => $appDir . "/cli-config.php"
                    ];
                    break;
            }

            if (!isset($whiteListedPackages[$packageName])) {
                // this package is not allowed to register templates
                continue;
            }

            // add any templates that this package has defined
            // this will overwrite any existing template of the same name, unless it is protected
            $extra = $package->getExtra();

            if (!empty($extra["silktide/lazy-boy"]["templates"]) && is_array($extra["silktide/lazy-boy"]["templates"])) {
                foreach ($extra["silktide/lazy-boy"]["templates"] as $templateName => $config) {

                    // prevent protected templates being overwritten
                    if (isset($protectedTemplates[$templateName])) {
                        $output->write("<info>LazyBoy:</info> <error>Package '$packageName' tried to overwrite the protected template '$templateName'</error>");
                    }

                    if (!empty($templates[$templateName])) {
                        $config = array_replace($templates[$templateName], $config);
                    }

                    // validate config
                    if (empty($config["template"]) || empty($config["output"])) {
                        $output->write("<info>LazyBoy:</info> <error>Invalid config for template '$templateName' in package '$packageName'</error>");
                        continue;
                    }

                    // check the template file exists
                    $packageDir = $composer->getInstallationManager()->getInstallPath($package);

                    $templateFile = self::getAbsolutePath($config["template"], $appDir, $packageDir);

                    if (!file_exists($templateFile)) {
                        $output->write("<info>LazyBoy:</info> <error>The template file '$templateFile' in package '$packageName' does not exist</error>");
                        continue;
                    }

                    $outputFile = self::getAbsolutePath($config["output"], $appDir);

                    // add the template to the array
                    $templates[$templateName] = [
                        "template" => $templateFile,
                        "replacements" => isset($config["replacements"])? $config["replacements"]: [],
                        "output" => $outputFile
                    ];
                }
            }

        }

        foreach ($templates as $template) {
            static::processTemplate($template["template"], $template["replacements"], $template["output"], $output);
        }
    }

    protected static function getAbsolutePath($path, $rootPath, $targetPath = null) {

        if (is_array($path)) {
            foreach ($path as $i => $item) {
                $path[$i] = self::getAbsolutePath($item, $rootPath, $targetPath);
            }
        } elseif (is_string($path) && strpos($path, $rootPath) === false) {
            if (empty($targetPath)) {
                $targetPath = $rootPath;
            }
            $path = $targetPath . "/" . ltrim($path, "/");
        }

        return $path;

    }

    protected static function processTemplate($templateFilePath, array $replacements = [], $outputFilePaths, IOInterface $output)
    {

        if (!is_array($outputFilePaths)) {
            $outputFilePaths = [$outputFilePaths];
        }

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
