<?php declare(strict_types=1);

namespace yii\web\router;

/**
 * Class Match
 * @package yii\web\router
 */
class Match
{
    /**
     * @var string|null
     */
    private $name;
    /**
     * @var array
     */
    private $parameters;
    /**
     * @var callable
     */
    private $handler;

    /**
     * Match constructor.
     * @param callable $handler
     * @param array $parameters
     * @param string|null $name
     */
    public function __construct(callable $handler, array $parameters = [], ?string $name = null)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->handler = $handler;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return callable
     */
    public function getHandler(): callable
    {
        return $this->handler;
    }
}
