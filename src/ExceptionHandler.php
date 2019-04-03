<?php declare(strict_types=1);

namespace yii\web;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ExceptionHandler
 * @package yii\web
 */
class ExceptionHandler implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * ExceptionHandler constructor.
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $response = $this->responseFactory->createResponse(500);
            $response->getBody()->write($e->getMessage());
            return $response;
        }
    }
}
