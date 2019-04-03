<?php declare(strict_types=1);

namespace yii\web\emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * SapiEmitter sends a response using PHP Server API
 * @package yii\web\emitter
 */
class SapiEmitter implements EmitterInterface
{
    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $header,
                    $value
                ), $header !== 'Set-Cookie', $status);
            }
        }

        $reason = $response->getReasonPhrase();

        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $status,
            ($reason ? ' ' . $reason : '')
        ), true, $status);

        echo $response->getBody();

        return true;
    }
}
