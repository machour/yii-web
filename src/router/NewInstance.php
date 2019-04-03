<?php declare(strict_types=1);

namespace yii\web\router;

/**
 * Class NewInstance
 * @package yii\web\router
 */
final class NewInstance
{
    /**
     * @var string
     */
    private $class;
    /**
     * @var string
     */
    private $method;

    /**
     * NewInstance constructor.
     * @param string $class
     * @param string $method
     */
    private function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * @param mixed ...$arguments
     */
    public function __invoke(...$arguments)
    {
        $controller = new $this->class;
        $controller->{$this->method}(...$arguments);
    }
}
