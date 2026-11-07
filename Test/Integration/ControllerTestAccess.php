<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Laminas\Http\Headers;
use Laminas\Stdlib\ParametersInterface;

/**
 * Typed access to the container, the request and the response of a controller test.
 *
 * The framework hands each of them out under a wider type than the test uses
 * (the container returns mixed, the request and response their bare
 * interfaces); every accessor asserts the concrete type once, so a test that
 * receives something else fails at that line instead of further down.
 */
trait ControllerTestAccess
{
    /**
     * Resolve a shared instance from the test container.
     *
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    protected function service(string $type): object
    {
        $service = $this->_objectManager->get($type);
        self::assertInstanceOf($type, $service);

        return $service;
    }

    /**
     * The HTTP request the next dispatch serves.
     *
     * @return HttpRequest
     */
    protected function httpRequest(): HttpRequest
    {
        $request = $this->getRequest();
        self::assertInstanceOf(HttpRequest::class, $request);

        return $request;
    }

    /**
     * The headers of the request the next dispatch serves.
     *
     * @return Headers
     */
    protected function requestHeaders(): Headers
    {
        $headers = $this->httpRequest()->getHeaders();
        self::assertInstanceOf(Headers::class, $headers);

        return $headers;
    }

    /**
     * The server parameters of the request the next dispatch serves.
     *
     * @return ParametersInterface<string, mixed>
     */
    protected function serverParameters(): ParametersInterface
    {
        $server = $this->httpRequest()->getServer();
        self::assertInstanceOf(ParametersInterface::class, $server);

        return $server;
    }

    /**
     * The HTTP response the last dispatch produced.
     *
     * @return HttpResponse
     */
    protected function httpResponse(): HttpResponse
    {
        $response = $this->getResponse();
        self::assertInstanceOf(HttpResponse::class, $response);

        return $response;
    }

    /**
     * The body of the response the last dispatch produced.
     *
     * @return string
     */
    protected function responseBody(): string
    {
        return (string)$this->httpResponse()->getBody();
    }
}
