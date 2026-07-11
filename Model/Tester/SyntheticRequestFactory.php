<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Tester;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\HttpFactory as HttpRequestFactory;
use Laminas\Stdlib\Parameters;

/**
 * Builds the synthetic storefront request the rule fields are evaluated
 * against: URI, path info, method, headers, server params (client IP, host,
 * query string), query/route params and the dispatched route parts — the
 * complete surface the filter fields read.
 */
class SyntheticRequestFactory
{
    /**
     * HTTP methods the simulator accepts.
     */
    private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    /**
     * @param HttpRequestFactory $requestFactory
     */
    public function __construct(
        private readonly HttpRequestFactory $requestFactory
    ) {
    }

    /**
     * Create a request object representing the simulated storefront call.
     *
     * @param string $path URI path with a leading slash, no query string
     * @param string $query Raw query string without the leading "?" ('' = none)
     * @param string $host Host name the request targets ('' = unknown)
     * @param string $method HTTP method (defaults to GET when invalid/empty)
     * @param string $userAgent User-Agent header value ('' = header absent)
     * @param string $clientIp REMOTE_ADDR of the simulated client ('' = unset)
     * @param string $referer Referer header value ('' = header absent)
     * @param array{route: string, controller: string, action: string, params: array<string, string>}|null $routeParts
     *        Dispatched route parts, or null when the route is unknown
     * @return HttpRequest
     */
    public function create(
        string $path,
        string $query,
        string $host,
        string $method,
        string $userAgent,
        string $clientIp,
        string $referer,
        ?array $routeParts
    ): HttpRequest {
        $request = $this->requestFactory->create();

        $uri = $path . ($query !== '' ? '?' . $query : '');
        $request->setRequestUri($uri);
        $request->setPathInfo($path);
        $this->applyMethod($request, $method);
        $this->applyServerParams($request, $uri, $query, $host, $clientIp);
        $this->applyHeaders($request, $host, $userAgent, $referer);
        $this->applyParams($request, $query, $routeParts);

        return $request;
    }

    /**
     * Set the HTTP method, falling back to GET for anything unrecognized
     * (laminas accepts custom method tokens, so the whitelist is enforced here).
     *
     * @param HttpRequest $request
     * @param string $method
     * @return void
     */
    private function applyMethod(HttpRequest $request, string $method): void
    {
        $method = strtoupper(trim($method));

        $request->setMethod(in_array($method, self::ALLOWED_METHODS, true) ? $method : 'GET');
    }

    /**
     * Populate the server params the fields read directly (client IP
     * resolution, query string, host).
     *
     * @param HttpRequest $request
     * @param string $uri
     * @param string $query
     * @param string $host
     * @param string $clientIp
     * @return void
     */
    private function applyServerParams(
        HttpRequest $request,
        string $uri,
        string $query,
        string $host,
        string $clientIp
    ): void {
        $server = [
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => $query,
        ];
        if ($host !== '') {
            $server['HTTP_HOST'] = $host;
        }
        if ($clientIp !== '') {
            $server['REMOTE_ADDR'] = $clientIp;
        }

        $request->setServer(new Parameters($server));
    }

    /**
     * Add the simulated HTTP headers (empty values mean "header absent").
     *
     * @param HttpRequest $request
     * @param string $host
     * @param string $userAgent
     * @param string $referer
     * @return void
     */
    private function applyHeaders(HttpRequest $request, string $host, string $userAgent, string $referer): void
    {
        $headers = $request->getHeaders();
        if ($host !== '') {
            $headers->addHeaderLine('Host', $host);
        }
        if ($userAgent !== '') {
            $headers->addHeaderLine('User-Agent', $userAgent);
        }
        if ($referer !== '') {
            $headers->addHeaderLine('Referer', $referer);
        }
    }

    /**
     * Fill query params, path-embedded route params and the dispatched route
     * parts, mirroring the request state after frontend routing.
     *
     * @param HttpRequest $request
     * @param string $query
     * @param array{route: string, controller: string, action: string, params: array<string, string>}|null $routeParts
     * @return void
     */
    private function applyParams(HttpRequest $request, string $query, ?array $routeParts): void
    {
        parse_str($query, $queryParams);
        $request->setQuery(new Parameters($queryParams));

        $params = $queryParams;
        if ($routeParts !== null) {
            $request->setRouteName($routeParts['route']);
            $request->setControllerName($routeParts['controller']);
            $request->setActionName($routeParts['action']);
            $params = array_merge($routeParts['params'], $queryParams);
        }

        $request->setParams($params);
    }
}
