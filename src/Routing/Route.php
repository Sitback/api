<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Routing\Route as IlluminateRoute;


class Route
{
    /**
     * Container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Route URI.
     *
     * @var string
     */
    protected $uri;

    /**
     * Array of HTTP methods.
     *
     * @var array
     */
    protected $methods;

    /**
     * Array of route action attributes.
     *
     * @var array
     */
    protected $action;

    /**
     * Array of versions this route will respond to.
     *
     * @var array
     */
    protected $versions;

    /**
     * Array of scopes for OAuth 2.0 authentication.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Indicates if the route is protected.
     *
     * @var bool
     */
    protected $protected = false;

    /**
     * Array of authentication providers.
     *
     * @var array
     */
    protected $authProviders = [];

    /**
     * The rate limit for this route.
     *
     * @var int
     */
    protected $rateLimit;

    /**
     * The expiration time for any rate limit set on this rate.
     *
     * @var int
     */
    protected $rateExpiration;

    /**
     * Controller instance.
     *
     * @var object
     */
    protected $controller;

    /**
     * Create a new route instance.
     *
     * @param \Illuminate\Container\Container $container
     * @param array|\Illuminate\Routing\Route $route
     * @param \Dingo\Api\Http\Request         $request
     *
     * @return void
     */
    public function __construct(Container $container, $route, Request $request)
    {
        $this->container = $container;

        $this->setupRoute($route, $request);
    }

    /**
     * Create the route from the existing route and request instance.
     *
     * @param array|\Illuminate\Routing\Route $route
     * @param \Dingo\Api\Http\Request         $request
     *
     * @return void
     */
    protected function setupRoute($route, Request $request)
    {
        if ($route instanceof IlluminateRoute) {
            $this->setupFromLaravelRoute($route, $request);
        } else {
            $this->setupFromLumenRoute($route, $request);
        }

        $this->makeController();

        $this->versions = array_pull($this->action, 'version');
        $this->scopes = array_pull($this->action, 'scopes', []);
        $this->protected = array_pull($this->action, 'protected', false);
        $this->authProviders = array_pull($this->action, 'providers', []);
        $this->rateLimit = array_pull($this->action, 'limit', 0);
        $this->rateExpiration = array_pull($this->action, 'expires', 0);

        if (is_string($this->authProviders)) {
            $this->authProviders = explode('|', $this->authProviders);
        }

    }

    /**
     * Make a controller instance from the "uses" action key if it's
     * in the controller format.
     *
     * @return void
     */
    protected function makeController()
    {
        if (! is_string($this->action['uses'])) {
            return;
        }

        if (str_contains($this->action['uses'], '@')) {
            list($controller, $method) = explode('@', $this->action['uses']);
        } else {
            $controller = $this->action['uses'];
        }

        $this->controller = $this->container->make($controller);
    }

    /**
     * Setup a new route from a Laravel route.
     *
     * @param \Illuminate\Routing\Route $route
     * @param \Dingo\Api\Http\Request   $request
     *
     * @return void
     */
    protected function setupFromLaravelRoute(IlluminateRoute $route, Request $request)
    {
        $this->uri = $route->getUri();
        $this->methods = $route->getMethods();
        $this->action = $route->getAction();
    }

    /**
     * Setup a new route from a Lumen route.
     *
     * @param array                   $route
     * @param \Dingo\Api\Http\Request $request
     *
     * @return void
     */
    protected function setupFromLumenRoute(array $route, Request $request)
    {
        $this->uri = ltrim($request->getRequestUri(), '/');
        $this->methods = (array) $request->getMethod();
        $this->action = $route[1];

        if ($request->getMethod() === 'GET') {
            $this->methods[] = 'HEAD';
        }
    }

    /**
     * Determine if the route is protected.
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->protected === true;
    }

    /**
     * Get the route scopes.
     *
     * @return array
     */
    public function scopes()
    {
        return $this->getScopes();
    }

    /**
     * Get the route scopes.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Get the route authentication providers.
     *
     * @return array
     */
    public function getAuthProviders()
    {
        return $this->authProviders;
    }

    /**
     * Get the rate limit for this route.
     *
     * @return int
     */
    public function getRateLimit()
    {
        return $this->rateLimit;
    }

    /**
     * Get the rate limit expiration time for this route.
     *
     * @return int
     */
    public function getLimitExpiration()
    {
        return $this->rateExpiration;
    }
}
