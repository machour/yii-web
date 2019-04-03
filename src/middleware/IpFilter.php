<?php


namespace yii\web\middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * IP filtering middleware
 *
 * @package yii\web\middleware
 */
class IpFilter implements MiddlewareInterface
{
    private $allowedIp;
    private $responseFactory;

    /**
     * IpFilter constructor.
     *
     * @param string $allowedIp The allowed IP
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(string $allowedIp, ResponseFactoryInterface $responseFactory)
    {
        $this->allowedIp = $allowedIp;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getServerParams()['REMOTE_ADDR'] !== $this->allowedIp) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Access denied!');
            return $response;
        }

        return $handler->handle($request);
    }
}
