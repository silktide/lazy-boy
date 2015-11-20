<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Test\Config;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Silktide\LazyBoy\Config\RouteLoader;
use Silktide\LazyBoy\Exception\RouteException;
use Silex\Application;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\YamlLoader;
use Silktide\LazyBoy\Security\SecurityContainer;

/**
 *
 */
class RouteLoaderTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Mockery\Mock|Application
     */
    protected $app;

    /**
     * @var \Mockery\Mock|SecurityContainer
     */
    protected $securityContainer;

    /** @var \Mockery\Mock $controllerCollection */
    protected $controllerCollection;

    public function setUp()
    {
        $this->controllerCollection = \Mockery::mock("Silex\\ContainerCollection");
        $this->app = \Mockery::mock("Silex\\Application");
        $this->securityContainer = \Mockery::mock("Silktide\\LazyBoy\\Security\\SecurityContainer");

        vfsStreamWrapper::register();
    }

    /**
     * @dataProvider routeProvider
     *
     * @param array $routes
     * @param string $exceptionPattern
     * @param array $expectedCalls
     * @throws RouteException
     */
    public function testRouteLoading(array $routes, $exceptionPattern, array $expectedCalls = []) {

        $this->controllerCollection->shouldReceive("bind");

        $loader = new RouteLoader($this->app, $this->securityContainer);

        foreach ($expectedCalls as $call) {
            $this->app->shouldReceive($call["method"])->with($call["url"], $call["action"])->once()->andReturn($this->controllerCollection);
        }

        try {
            $loader->parseRoutes($routes);
            if (!empty($exceptionPattern)) {
                $this->fail("The RouteLoader did not throw an exception as expected");
            }
        } catch (RouteException $e) {
            if (empty($exceptionPattern)) {
                throw $e;
            } else {
                $this->assertRegExp($exceptionPattern, $e->getMessage());
            }
        }

    }

    public function testRouteFileLoading() {

        $counts = [];
        $this->controllerCollection->shouldReceive("bind")->andReturnUsing(function($routeName) use (&$counts) {
            if (empty($counts[$routeName])) {
                $counts[$routeName] = 0;
            }
            ++$counts[$routeName];
        });

        $this->app->shouldReceive("get")->andReturn($this->controllerCollection);
        $this->app->shouldReceive("post")->andReturn($this->controllerCollection);

        $loader = new RouteLoader($this->app, $this->securityContainer);
        $loader->addLoader(new JsonLoader());
        $loader->addLoader(new YamlLoader());

        try {
            $loader->parseRoutes(123);
            $this->fail("Should not be able to parse routes with an invalid routes argument");
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\InvalidArgumentException", $e);
            unset($e);
        }

        try {
            $loader->parseRoutes("nonExistentFile");
            $this->fail("Should not be able to parse routes with a non existent file");
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Silktide\\LazyBoy\\Exception\\RouteException", $e);
            $this->assertRegExp("/Cannot load routes/", $e->getMessage());
            unset($e);
        }

        vfsStreamWrapper::setRoot(new vfsStreamDirectory("test", 0777));

        $routesFile = vfsStream::url("test/test.json");
        $file = vfsStream::newFile("test.json", 0777);
        $file->setContent("not JSON");
        vfsStreamWrapper::getRoot()->addChild($file);

        try {
            $loader->parseRoutes($routesFile);
            $this->fail("Should not be able to parse routes from an invalid JSON file");
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Silktide\\LazyBoy\\Exception\\RouteException", $e);
            $this->assertRegExp("/Could not load the JSON file/", $e->getMessage());
            unset($e);
        }

        // Test that we can correctly parse JSON
        $url = "url";
        $action = "action";
        $jsonRoute = "jsonRoute";
        $content = json_encode(["routes" => [$jsonRoute => ["url" => $url, "action" => $action]]]);
        $file->setContent($content);
        $loader->parseRoutes($routesFile);


        $routesFile = vfsStream::url("test/test.yaml");
        $file = vfsStream::newFile("test.yaml", 0777);
        $file->setContent("- \"Invalid Yaml");;
        vfsStreamWrapper::getRoot()->addChild($file);

        try{
            $loader->parseRoutes($routesFile);
            $this->fail("Should not be able to parse routes from an invalid Yaml file");
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Silktide\\LazyBoy\\Exception\\RouteException", $e);
            $this->assertRegExp("/Could not load the YAML file/", $e->getMessage());
        }

        // Test that we can correctly parse YAML
        $url = "url";
        $action = "action";
        $yamlRoute = "yamlRoute";
        $content ="routes:\n  $yamlRoute:\n    url: ".$url."\n    action: ".$action;
        $file->setContent($content);
        $loader->parseRoutes($routesFile);

        $this->assertArrayHasKey($jsonRoute, $counts, "The route in the JSON file was not parsed");
        $this->assertEquals(1, $counts[$jsonRoute], "The route in the JSON file was not parsed exactly once");
        $this->assertArrayHasKey($yamlRoute, $counts, "The route in the YAML file was not parsed");
        $this->assertEquals(1, $counts[$yamlRoute], "The route in the YAML file was not parsed exactly once");
    }

    public function routeProvider()
    {
        return [
            [
                ["invalid" => "root"],
                "/'routes'/"
            ],
            [
                ["routes" => "not an array"],
                "/not in the correct format/"
            ],
            [
                [
                    "routes" => [
                        ["url" => "url"]
                    ]
                ],
                "/route is missing required elements/"
            ],
            [
                [
                    "routes" => [
                        ["action" => "action"]
                    ]
                ],
                "/route is missing required elements/"
            ],
            [
                [
                    "routes" => [
                        [
                            "url" => "url",
                            "action" => "action",
                            "method" => "invalid"
                        ]
                    ]
                ],
                "/The method .* is not allowed/"
            ],
            [
                [
                    "routes" => [
                        [
                            "url" => "url",
                            "action" => "action",
                            "method" => "post"
                        ]
                    ]
                ],
                "",
                [
                    [
                        "method" => "post",
                        "url" => "url",
                        "action" => "action"
                    ]
                ]
            ],
            [
                [
                    "routes" => [
                        [
                            "url" => "url",
                            "action" => "action"
                        ]
                    ]
                ],
                "",
                [
                    [
                        "method" => "get",
                        "url" => "url",
                        "action" => "action"
                    ]
                ]
            ]

        ];
    }

    public function tearDown()
    {
        \Mockery::close();
    }

}
 