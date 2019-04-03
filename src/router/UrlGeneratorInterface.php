<?php

namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;

interface UrlGeneratorInterface
{
    public const TYPE_ABSOLUTE = 'absolute';
    public const TYPE_RELATIVE = 'relative';

    /**
     * @param string $name
     * @param ServerRequestInterface $request
     * @param array $parameters
     * @param string $type
     * @return string
     * @throws NoRoute
     */
    public function generate(string $name, ServerRequestInterface $request, array $parameters = [], string $type = self::TYPE_ABSOLUTE): string;
}
