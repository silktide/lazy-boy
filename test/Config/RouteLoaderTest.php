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

/**
 *
 */
class RouteLoaderTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Mockery\Mock|Application
     */
    protected $app;

    public function setUp()
    {
        $this->app = \Mockery::mock("Silex\\Application");

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

        $loader = new RouteLoader($this->app);

        foreach ($expectedCalls as $call) {
            $this->app->shouldReceive($call["method"])->with($call["url"], $call["action"])->once();
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
        $loader = new RouteLoader($this->app);

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
            $this->fail("Should not be able to parse routes from a file that isn't in JSON format");
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Silktide\\LazyBoy\\Exception\\RouteException", $e);
            $this->assertRegExp("/could not be parsed as JSON/", $e->getMessage());
            unset($e);
        }

        $url = "url";
        $action = "action";

        $this->app->shouldReceive("get")->with($url, $action)->once();

        $content = json_encode(["routes" => ["route" => ["url" => $url, "action" => $action]]]);
        $file->setContent($content);
        $loader->parseRoutes($routesFile);

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
 