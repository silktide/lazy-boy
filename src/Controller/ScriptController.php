<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Controller;

use Composer\Script\Event;
use Silktide\LazyBoy\Exception\InstallationException;

/**
 *
 */
class ScriptController 
{

    public static function install(Event $event)
    {
        $composer = $event->getComposer();

        $templates = [];

        // template dir
        $templateDir = realpath(__DIR__ . "../templates") . "/";
        // get app dir
        $appDir = realpath($composer->getConfig()->get("vendor-dir") . "/../");

        // check for puzzle-di
        $puzzleConfigUseStatement = "";
        $puzzleConfigLoadFiles = "";
        $package = $composer->getPackage();
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
                '$puzzleConfigs = PuzzleConfig::getConfigPaths("silktide/syringe");
$builder->addConfigFiles($puzzleConfigs);';
        }

        $templates = [
            "app" => [
                $templateDir . "app/config/app.json.temp",
                ["appDir" => $appDir],
                $appDir . "/app/config/app.json"
            ],
            "routes" => [
                $templateDir . "app/config/routes.json.temp",
                [],
                $appDir . "/app/config/routes.json"
            ],
            "services" => [
                $templateDir . "app/config/services.json.temp",
                [],
                $appDir . "/app/config/services.json"
            ],
            "bootstrap" => [
                $templateDir . "app/bootstrap.php.temp",
                [
                    "puzzleConfigUseStatement" => $puzzleConfigUseStatement,
                    "puzzleConfigLoadFiles" => $puzzleConfigLoadFiles
                ],
                $appDir . "/app/bootstrap.php"
            ],
            "console" => [
                $templateDir . "console.php.temp",
                [],
                $appDir . "/console.php"
            ],
            "index" => [
                $templateDir . "index.php.temp",
                [],
                $appDir . "/index.php"
            ]
        ];

        foreach ($templates as $template) {
            static::processTemplate($template[0], $template[1], $template[2]);
        }
    }

    protected static function processTemplate($templateFilePath, array $replacements = [], $outputFilePath = "")
    {
        if (!file_exists($templateFilePath)) {
            throw new InstallationException("The template file '$templateFilePath' does not exist");
        }
        $contents = file_get_contents($templateFilePath);

        foreach ($replacements as $search => $replacement) {
            $search = '{{' . $search . '}}';
            $contents = str_replace($search, $replacement, $contents);
        }

        file_put_contents($outputFilePath, $contents);
    }

} 