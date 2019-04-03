<?php

namespace yii\web\middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use yii\web\router\NoMatch;
use yii\web\router\UrlMatcherInterface;

/**
 * Routing middleware.
 *
 * @see MiddlewareInterface
 */
class Router implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var UrlMatcherInterface
     */
    private $matcher;

    /**
     * Router constructor.
     * @param UrlMatcherInterface $matcher
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(UrlMatcherInterface $matcher, ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $match = $this->matcher->match($request);
            $matchHandler = $match->getHandler();

            $response = $this->responseFactory->createResponse(200);
            // TODO: support returning values and formatting it?
            return $matchHandler($match->getParameters(), $request, $response);
        } catch (NoMatch $e) {
            return $handler->handle($request);
        }
    }
}
