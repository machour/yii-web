<?php declare(strict_types=1);

namespace yii\web\router;

/**
 * NoRoute represents an exception thrown when calling an unknown route.
 *
 * @package yii\web\router
 */
class NoRoute extends \Exception
{
    /**
     * @var string The route name
     */
    private $name;

    /**
     * NoRoute constructor.
     *
     * @param string $name The route name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        parent::__construct("There is no route named \"$name\"");
    }

    /**
     * @return string The route name
     */
    public function getName(): string
    {
        return $this->name;
    }
}
