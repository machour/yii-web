<?php declare(strict_types=1);

namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class Group implements RouterInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $before;

    /**
     * @var MiddlewareInterface[]
     */
    private $after;

    /**
     * @var Route[]
     */
    private $routes;

    /**
     * @var Route[]
     */
    private $namedRoutes = [];

    /**
     * Group constructor.
     * @param MiddlewareInterface[] $before
     * @param MiddlewareInterface[] $after
     * @param Route[] $routes
     */
    public function __construct(array $routes, array $before = [], array $after = [])
    {
        $this->before = $before;
        $this->after = $after;

        $this->routes = $routes;
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name !== null) {
                $this->namedRoutes[$name] = $route;
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * TODO compile regexps into concatenated one
     * @see https://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
     * @see https://medium.com/@nicolas.grekas/making-symfonys-router-77-7x-faster-1-2-958e3754f0e1
     */
    public function match(ServerRequestInterface $request): Match
    {
        foreach ($this->routes as $route) {

            $result = $route->parseRequest($request);

            if (!$result) {
                continue;
            }

            if ($route->getCallback() === null) {
                $route = $route->__toString();
                throw new NoHandler("\"$route\" has no handler.");
            }

            return new Match($route->getCallback(), $result[1], $result[0]);
        }

        throw new NoMatch($request);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $name, ServerRequestInterface $request, array $parameters = [], string $type = self::TYPE_ABSOLUTE): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new NoRoute($name);
        }

        $route = $this->namedRoutes[$name];

        $parameters = array_merge($route->getDefaults(), $parameters);

        return $route->getPattern();

        // TODO: implement proper generation

        // match the route part first
        if ($route !== $route->getPattern()) {
            if ($this->getRegex($route) !== null && preg_match($this->getRegex($route), $name, $matches)) {
                $matches = $this->substitutePlaceholderNames($route, $matches);
                foreach ($this->_routeParams as $name => $token) {
                    if (isset($this->defaults[$name]) && strcmp($this->defaults[$name], $matches[$name]) === 0) {
                        $tr[$token] = '';
                    } else {
                        $tr[$token] = $matches[$name];
                    }
                }
            } else {
                $this->createStatus = self::CREATE_STATUS_ROUTE_MISMATCH;
                return false;
            }
        }

        // match default params
        // if a default param is not in the route pattern, its value must also be matched
        foreach ($this->defaults as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                continue;
            }
            if (!isset($params[$name])) {
                // allow omit empty optional params
                // @see https://github.com/yiisoft/yii2/issues/10970
                if (in_array($name, $this->placeholders) && strcmp($value, '') === 0) {
                    $params[$name] = '';
                } else {
                    $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                    return false;
                }
            }
            if (strcmp($params[$name], $value) === 0) { // strcmp will do string conversion automatically
                unset($params[$name]);
                if (isset($this->_paramRules[$name])) {
                    $tr["<$name>"] = '';
                }
            } elseif (!isset($this->_paramRules[$name])) {
                $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                return false;
            }
        }

        // match params in the pattern
        foreach ($this->_paramRules as $name => $rule) {
            if (isset($params[$name]) && !is_array($params[$name]) && ($rule === '' || preg_match($rule, $params[$name]))) {
                $tr["<$name>"] = $this->encodeParams ? urlencode($params[$name]) : $params[$name];
                unset($params[$name]);
            } elseif (!isset($this->defaults[$name]) || isset($params[$name])) {
                $this->createStatus = self::CREATE_STATUS_PARAMS_MISMATCH;
                return false;
            }
        }

        $url = $this->trimSlashes(strtr($this->_template, $tr));
        if ($this->host !== null) {
            $pos = strpos($url, '/', 8);
            if ($pos !== false) {
                $url = substr($url, 0, $pos) . preg_replace('#/+#', '/', substr($url, $pos));
            }
        } elseif (strpos($url, '//') !== false) {
            $url = preg_replace('#/+#', '/', trim($url, '/'));
        }

        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }

        return $url;
    }
}
