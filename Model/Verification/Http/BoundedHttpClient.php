<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verification\Http;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * PSR-18 client (and PSR-17 request/stream factory) preconfigured with a strict
 * wall-clock budget for outbound siteverify / assessment calls.
 *
 * Composition is used over inheritance because {@see Psr18Client} is final.
 * The transport caps a single request at the configured budget (default 2.0s,
 * including TCP/TLS, request body, response read) so a degraded provider
 * endpoint cannot occupy a PHP-FPM worker beyond that budget.
 */
class BoundedHttpClient implements ClientInterface, RequestFactoryInterface, StreamFactoryInterface
{
    private const DEFAULT_TIMEOUT_SECONDS = 2.0;

    private readonly Psr18Client $delegate;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $timeout = $config->getHttpTimeout();
        if ($timeout <= 0.0) {
            $timeout = self::DEFAULT_TIMEOUT_SECONDS;
        }

        $this->delegate = new Psr18Client(
            HttpClient::create([
                'timeout' => $timeout,
                'max_duration' => $timeout,
                'headers' => [
                    'User-Agent' => 'Hryvinskyi-InvisibleCaptcha/3.0',
                ],
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->delegate->sendRequest($request);
    }

    /**
     * @inheritDoc
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return $this->delegate->createRequest($method, $uri);
    }

    /**
     * @inheritDoc
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return $this->delegate->createStream($content);
    }

    /**
     * @inheritDoc
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return $this->delegate->createStreamFromFile($filename, $mode);
    }

    /**
     * @inheritDoc
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return $this->delegate->createStreamFromResource($resource);
    }
}
