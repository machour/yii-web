<?php declare(strict_types=1);

namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Route defines a mapping from URL to callback / name and vice versa
 */
class Route
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $methods;
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $host;

    /**
     * TODO: should it be optional?
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $defaults = [];

    public $placeholders = [];

    /**
     * @var string the template for generating a new URL. This is derived from [[pattern]] and is used in generating URL.
     */
    private $_template;
    /**
     * @var string the regex for matching the route part. This is used in generating URL.
     */
    private $_routeRule;
    /**
     * @var array list of regex for matching parameters. This is used in generating URL.
     */
    private $_paramRules = [];
    /**
     * @var array list of parameters used in the route.
     */
    private $_routeParams = [];

    public function __construct()
    {

    }

    public static function get(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::GET];
        $new->pattern = $pattern;
        return $new;
    }

    public static function post(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::POST];
        $new->pattern = $pattern;
        return $new;
    }

    public static function put(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::PUT];
        $new->pattern = $pattern;
        return $new;
    }

    public static function delete(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::DELETE];
        $new->pattern = $pattern;
        return $new;
    }

    public static function patch(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::PATCH];
        $new->pattern = $pattern;
        return $new;
    }

    public static function head(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::HEAD];
        $new->pattern = $pattern;
        return $new;
    }

    public static function options(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::OPTIONS];
        $new->pattern = $pattern;
        return $new;
    }

    public static function methods(array $methods, string $pattern): self
    {
        // TODO: should we validate methods?
        $new = new static();
        $new->methods = $methods;
        $new->pattern = $pattern;
        return $new;
    }

    public static function allMethods(string $pattern): self
    {
        $new = new static();
        $new->methods = Method::ALL;
        $new->pattern = $pattern;
        return $new;
    }

    public function name(string $name): self
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }

    public function host(string $host): self
    {
        $new = clone $this;
        $new->host = rtrim($host, '/');
        return $new;
    }

    /**
     * Parameter validation rules indexed by parameter names
     *
     * @param array $parameters
     * @return Route
     */
    public function parameters(array $parameters): self
    {
        $new = clone $this;
        $new->parameters = $parameters;
        return $new;
    }

    /**
     * Parameter default values indexed by parameter names
     *
     * @param array $defaults
     * @return Route
     */
    public function defaults(array $defaults): self
    {
        $new = clone $this;
        $new->defaults = $defaults;
        return $new;
    }

    /**
     * // TODO: should we allow adding middlewares here?
     *
     * @param callable $callback
     * @return Route
     */
    public function to(callable $callback): self
    {
        $new = clone $this;
        $new->callback = $callback;
        return $new;
    }

    public function __toString()
    {
        $result = '';

        if ($this->name !== null) {
            $result .= '[' . $this->name . '] ';
        }

        if ($this->methods !== null) {
            $result .= implode(',', $this->methods) . ' ';
        }
        if ($this->host !== null && strrpos($this->pattern, $this->host) === false) {
            $result .= $this->host . '/';
        }
        $result .= $this->pattern;

        if ($result === '') {
            return '/';
        }

        return $result;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return callable
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @param $request
     * @return array|bool
     */
    public function parseRequest(ServerRequestInterface $request)
    {
        if (!in_array($request->getMethod(), $this->getMethods(), true)) {
            return false;
        }

        $this->compilePattern();


        $suffix = ''; // (string) ($this->suffix === null ? $manager->suffix : $this->suffix);
        $pathInfo = $request->getUri()->getPath();
        $normalized = false;
        /*
        if ($this->hasNormalizer($manager)) {
            $pathInfo = $this->getNormalizer($manager)->normalizePathInfo($pathInfo, $suffix, $normalized);
        }*/
        if ($suffix !== '' && $pathInfo !== '') {
            $n = strlen($suffix);
            if (substr_compare($pathInfo, $suffix, -$n, $n) === 0) {
                $pathInfo = substr($pathInfo, 0, -$n);
                if ($pathInfo === '') {
                    // suffix alone is not allowed
                    return false;
                }
            } else {
                return false;
            }
        }
        if ($this->host !== null) {
            $pathInfo = strtolower($request->getUri()->getHost()) . ($pathInfo === '' ? '' : '/' . $pathInfo);
        }

        if (!preg_match($this->pattern, $pathInfo, $matches)) {
            return false;
        }
        $matches = $this->substitutePlaceholderNames($matches);
        foreach ($this->defaults as $name => $value) {
            if (!isset($matches[$name]) || $matches[$name] === '') {
                $matches[$name] = $value;
            }
        }
        $params = $this->defaults;
        $tr = [];
        foreach ($matches as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                $tr[$this->_routeParams[$name]] = $value;
                unset($params[$name]);
            } elseif (isset($this->_paramRules[$name])) {
                $params[$name] = $value;
            }
        }
        if ($this->_routeRule !== null) {
            $route = strtr($this->name, $tr);
        } else {
            $route = $this->name;
        }
        /*
        if ($normalized) {
            // pathInfo was changed by normalizer - we need also normalize route
            return $this->getNormalizer($manager)->normalizeRoute([$route, $params]);
        }*/
        return [$route, $params];
    }

    public function compilePattern()
    {
        $this->pattern = $this->trimSlashes($this->pattern);

        if ($this->name !== null) {
            $this->name = trim($this->name, '/');
        }
        if ($this->host !== null) {
            $this->host = rtrim($this->host, '/');
            $this->pattern = rtrim($this->host . '/' . $this->pattern, '/');
        } elseif ($this->pattern === '') {
            $this->_template = '';
            $this->pattern = '#^/$#u'; // FIXME: no / in pattern in Yii2
            return;
        } elseif (($pos = strpos($this->pattern, '://')) !== false) {
            if (($pos2 = strpos($this->pattern, '/', $pos + 3)) !== false) {
                $this->host = substr($this->pattern, 0, $pos2);
            } else {
                $this->host = $this->pattern;
            }
        } elseif (strncmp($this->pattern, '//', 2) === 0) {
            if (($pos2 = strpos($this->pattern, '/', 2)) !== false) {
                $this->host = substr($this->pattern, 0, $pos2);
            } else {
                $this->host = $this->pattern;
            }
        } else {
            $this->pattern = '/' . $this->pattern . '/';
        }
        if ($this->name !== null && strpos($this->name, '<') !== false && preg_match_all('/<([\w._-]+)>/', $this->name, $matches)) {
            foreach ($matches[1] as $name) {
                $this->_routeParams[$name] = "<$name>";
            }
        }
        $this->translatePattern();
    }
    /**
     * Prepares [[$pattern]] on rule initialization - replace parameter names by placeholders.
     *
     * @param bool $allowAppendSlash Defines position of slash in the param pattern in [[$pattern]].
     * If `false` slash will be placed at the beginning of param pattern. If `true` slash position will be detected
     * depending on non-optional pattern part.
     */
    private function translatePattern($allowAppendSlash = true): void
    {
        $tr = [
            '.' => '\\.',
            '*' => '\\*',
            '$' => '\\$',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
        ];
        $tr2 = [];
        $requiredPatternPart = $this->pattern;
        $oldOffset = 0;
        if (preg_match_all('/<([\w._-]+):?([^>]+)?>/', $this->pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $appendSlash = false;
            foreach ($matches as $match) {
                $name = $match[1][0];
                $pattern = isset($match[2][0]) ? $match[2][0] : '[^\/]+';
                $placeholder = 'a' . hash('crc32b', $name); // placeholder must begin with a letter
                $this->placeholders[$placeholder] = $name;
                if (array_key_exists($name, $this->defaults)) {
                    $length = strlen($match[0][0]);
                    $offset = $match[0][1];
                    $requiredPatternPart = str_replace("/{$match[0][0]}/", '//', $requiredPatternPart);
                    if (
                        $allowAppendSlash
                        && ($appendSlash || $offset === 1)
                        && (($offset - $oldOffset) === 1)
                        && isset($this->pattern[$offset + $length])
                        && $this->pattern[$offset + $length] === '/'
                        && isset($this->pattern[$offset + $length + 1])
                    ) {
                        // if pattern starts from optional params, put slash at the end of param pattern
                        // @see https://github.com/yiisoft/yii2/issues/13086
                        $appendSlash = true;
                        $tr["<$name>/"] = "((?P<$placeholder>$pattern)/)?";
                    } elseif (
                        $offset > 1
                        && $this->pattern[$offset - 1] === '/'
                        && (!isset($this->pattern[$offset + $length]) || $this->pattern[$offset + $length] === '/')
                    ) {
                        $appendSlash = false;
                        $tr["/<$name>"] = "(/(?P<$placeholder>$pattern))?";
                    }
                    $tr["<$name>"] = "(?P<$placeholder>$pattern)?";
                    $oldOffset = $offset + $length;
                } else {
                    $appendSlash = false;
                    $tr["<$name>"] = "(?P<$placeholder>$pattern)";
                }
                if (isset($this->_routeParams[$name])) {
                    $tr2["<$name>"] = "(?P<$placeholder>$pattern)";
                } else {
                    $this->_paramRules[$name] = $pattern === '[^\/]+' ? '' : "#^$pattern$#u";
                }
            }
        }
        // we have only optional params in route - ensure slash position on param patterns
        if ($allowAppendSlash && trim($requiredPatternPart, '/') === '') {
            $this->translatePattern(false);
            return;
        }
        $this->_template = preg_replace('/<([\w._-]+):?([^>]+)?>/', '<$1>', $this->pattern);
        $this->pattern = '#^' . trim(strtr($this->_template, $tr), '/') . '$#u';
        // if host starts with relative scheme, then insert pattern to match any
        if ($this->host && strncmp($this->host, '//', 2) === 0) {
            $this->pattern = substr_replace($this->pattern, '[\w]+://', 2, 0);
        }
        if (!empty($this->_routeParams)) {
            $this->_routeRule = '#^' . strtr($this->name, $tr2) . '$#u';
        }
    }

    /**
     * Trim slashes in passed string. If string begins with '//', two slashes are left as is
     * in the beginning of a string.
     *
     * @param string $string
     * @return string
     */
    private function trimSlashes($string)
    {
        if (strncmp($string, '//', 2) === 0) {
            return '//' . trim($string, '/');
        }
        return trim($string, '/');
    }

    /**
     * Iterates over [[placeholders]] and checks whether each placeholder exists as a key in $matches array.
     * When found - replaces this placeholder key with a appropriate name of matching parameter.
     * Used in [[parseRequest()]], [[createUrl()]].
     *
     * @param array $matches result of `preg_match()` call
     * @return array input array with replaced placeholder keys
     * @see placeholders
     */
    protected function substitutePlaceholderNames(array $matches)
    {
        foreach ($this->placeholders as $placeholder => $name) {
            if (isset($matches[$placeholder])) {
                $matches[$name] = $matches[$placeholder];
                unset($matches[$placeholder]);
            }
        }
        return $matches;
    }


}
