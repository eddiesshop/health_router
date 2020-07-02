<?php

namespace App\Classes;

class RouteMetaData
{
    private const ID_SEGMENT = '{id}';

    /**
     * @var string
     */
    protected $originalPath;

    /**
     * @var string
     */
    protected $standardizedPath;

    /**
     * @var string
     */
    protected $controller;

    /**
     * @var string
     */
    protected $method;

    public function __construct($path, $controller, $method)
    {
        $this->controller = "\\App\\Controllers\\$controller";
        $this->method = $method;
        $this->setPaths($path);
    }

    protected function setPaths($path)
    {
        $this->originalPath = $path;
        $this->standardizedPath = $this->standardizeRoute($path);
    }

    /**
     * @return string
     */
    public function getOriginalPath()
    {
        return $this->originalPath;
    }

    /**
     * @return string
     */
    public function getStandardizedPath()
    {
        return $this->standardizedPath;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return new $this->controller;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $route
     * @return string
     */
    public static function standardizeRoute($route)
    {
        // Remove forward-slash if starts with forward-slash
        if (strpos($route, '/') === 0)
            $route = substr($route, 1);

        $explodedRoute = explode('/', $route);

        foreach ($explodedRoute as $key => &$segment) {

            /*
             * Generating standardized routes to help with route lookup.
             * Discounting this loop here of course, it should be
             * O(1) lookup when a request comes in. For the
             * instances where the route is not found,
             * we can fall back to the method of
             * checking the original route.
             */
            if (strpos($segment, '{') !== false && strpos($segment, '}') !== false) {
                $segment = self::ID_SEGMENT;
            } else if (is_numeric($segment)) {
                $segment = self::ID_SEGMENT;
            } else if ($key % 2 === 1) {
                $segment = self::ID_SEGMENT;
            }
        }

        return implode('/', $explodedRoute);
    }

    /**
     * @param string $route
     * @return int[]
     */
    public static function extractParams($route)
    {
        // Remove forward-slash if starts with forward-slash
        if (strpos($route, '/') === 0)
            $route = substr($route, 1);

        $explode = explode('/', $route);

        return array_filter($explode,
            function ($key) {
                return $key % 2 === 1;
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}