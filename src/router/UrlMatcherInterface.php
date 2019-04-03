<?php

namespace yii\web\router;

use Psr\Http\Message\ServerRequestInterface;

interface UrlMatcherInterface
{
    /**
     * @param ServerRequestInterface $request The request to be matched against
     * @return Match
     * @throws NoHandler
     * @throws NoMatch
     */
    public function match(ServerRequestInterface $request): Match;
}
