<?php
namespace App\Classes;

use Error;
use Exception;

/**
 * Class Route
 * @package App\Classes
 *
 * @method static void get(string $route, string $controllerPath)
 * @method static void post(string $route, string $controllerPath)
 * @method static void patch(string $route, string $controllerPath)
 * @method static void delete(string $route, string $controllerPath)
 */

final class Route
{
    private const GET = 'GET';
    private const POST = 'POST';
    private const PATCH = 'PATCH';
    private const DELETE = 'DELETE';

    /**
     * @var Route $instance
     */
    private static $instance = null;

    /**
     * @var array[]
     */
    protected $routes = [
        self::GET => [],
        self::POST => [],
        self::PATCH => [],
        self::DELETE => [],
    ];

    private function __construct()
    {
        // Private to prevent initiation with new keyword from outside this class.
    }

    protected function defineMethod($httpMethod, $route, $controllerPath)
    {
        $exploded = explode('@', $controllerPath);

        if (count($exploded) !== 2)
            throw new Exception("Incorrect Controller path provided. Format should be Controller@methodName");

        list($controller, $method) = $exploded;

        $routeMetaData = new RouteMetaData($route, $controller, $method);

        $this->routes[$httpMethod][$routeMetaData->getStandardizedPath()] = $routeMetaData;
    }

    protected function invokeMethod($httpMethod, $route, $payload = null)
    {
        $standardizedRoute = $this->getStandardRouteOrAbortIfRouteDoesNotExist($httpMethod, $route);

        $params = RouteMetaData::extractParams($route);

        if (! is_null($payload))
            $params[] = $payload;

        /* @var RouteMetaData $routeMetaData */
        $routeMetaData = @$this->routes[$httpMethod][$standardizedRoute];

        if (is_null($routeMetaData)) {
            // Standardized route lookup failed. Go with slower lookup based on original path.

            foreach ($this->routes[$httpMethod] as $savedRouteMetaData) {
                /* @var RouteMetaData $savedRouteMetaData */
                if ($savedRouteMetaData->getOriginalPath() === $route) {
                    $routeMetaData = $savedRouteMetaData;
                    break;
                }
            }

            // If still not found, respond with 404
            if (is_null($routeMetaData))
                return http_response_code(404);
        }

        try {
            $controller = $routeMetaData->getController();
        } catch (Error $error) {
            throw new Exception("Controller does not exist for specified route!");
        }

        return call_user_func_array([$controller, $routeMetaData->getMethod()], $params);
    }

    public static function resource($route)
    {
        $exploded = explode('.', $route);

        $controller = implode('', array_map(function($segment){
            return ucfirst(strtolower($segment));
        }, $exploded)) . 'Controller';

        $lastIndex = count($exploded) - 1;

        for ($key= 0; $key <= $lastIndex; $key++) {
            if ($key % 2 === 1) { // Odd Key
                array_splice($exploded, $key, 0, '{id}');
            }

            if ($key === $lastIndex) {
                $exploded[] = '{id}';
            }
        }

        $fauxStandardizedRoute = implode('/', $exploded);

        $instance = static::getInstance();

        // Define Routes With IDs
        $instance->defineMethod(self::GET, $fauxStandardizedRoute, "$controller@get");
        $instance->defineMethod(self::PATCH, $fauxStandardizedRoute, "$controller@update");
        $instance->defineMethod(self::DELETE, $fauxStandardizedRoute, "$controller@delete");

        // Define Routes with dropped last ID param
        $fauxStandardizedRoute = substr($fauxStandardizedRoute, 0, strrpos($fauxStandardizedRoute, '/'));

        $instance->defineMethod(self::GET, $fauxStandardizedRoute, "$controller@index");
        $instance->defineMethod(self::POST, $fauxStandardizedRoute, "$controller@create");
    }

    /**
     * Any non-static calls can be assumed to be invoked by
     * the application when a route is accessed/called.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $instance = static::getInstance();

        array_unshift($arguments, strtoupper($name));

        return call_user_func_array([$instance, 'invokeMethod'], $arguments);
    }

    /**
     * Any static calls can be assumed to be route definitions.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $instance = static::getInstance();

        array_unshift($arguments, strtoupper($name));

        return call_user_func_array([$instance, 'defineMethod'], $arguments);
    }

    /**
     * Implementing Singleton pattern.
     *
     * @return Route
     */
    public static function getInstance()
    {
        if (self::$instance === null)
            return self::$instance = new self();

        return self::$instance;
    }

    private function getStandardRouteOrAbortIfRouteDoesNotExist($httpMethod, $route)
    {
        $standardizedRoute = RouteMetaData::standardizeRoute($route);

        if (! array_key_exists($standardizedRoute, $this->routes[$httpMethod]))
            return http_response_code(404);

        return $standardizedRoute;
    }
}