<?php declare(strict_types=1);

namespace yii\web\router;

/**
 * Class Redirect
 * @package yii\web\router
 */
final class Redirect
{
    /**
     * @var int
     */
    private $status = 301;
    /**
     * @var
     */
    private $routeName;
    /**
     * @var array
     */
    private $routeParameters = [];
    /**
     * @var
     */
    private $url;

    /**
     * @param string $name
     * @param array $parameters
     * @return Redirect
     */
    public static function toRoute(string $name, array $parameters = []): Redirect
    {
        $new = new static();
        $new->routeName = $name;
        $new->routeParameters = $parameters;
        return $new;
    }

    /**
     * @param string $url
     * @return Redirect
     */
    public static function toUrl(string $url): Redirect
    {
        $new = new static();
        $new->url = $url;
        return $new;
    }

    /**
     * @param int $status
     * @return Redirect
     */
    public function withStatus(int $status): Redirect
    {
        $new = clone $this;
        $new->status = $status;
        return $new;
    }

    /**
     * @param mixed ...$arguments
     */
    public function __invoke(...$arguments)
    {
        // TODO: how to implement redirection here?
    }
}
