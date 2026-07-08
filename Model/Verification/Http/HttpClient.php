<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verification\Http;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\HttpClientInterface;
use Hryvinskyi\InvisibleCaptcha\Exception\HttpClientException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * HttpClientInterface adapter over the bounded PSR-18 transport. Fails closed
 * and fast: any transport error or non-2xx status is surfaced as
 * {@see HttpClientException} so callers can return a closed verification result.
 */
class HttpClient implements HttpClientInterface
{
    /**
     * @param BoundedHttpClient $transport PSR-18 client + PSR-17 factories with a strict budget.
     */
    public function __construct(
        private readonly BoundedHttpClient $transport
    ) {
    }

    /**
     * @inheritDoc
     */
    public function post(string $url, string $body, array $headers = []): string
    {
        try {
            $request = $this->transport
                ->createRequest('POST', $url)
                ->withBody($this->transport->createStream($body));

            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            $response = $this->transport->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new HttpClientException($e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new HttpClientException($e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new HttpClientException(sprintf('Endpoint returned HTTP %d', $status));
        }

        return (string)$response->getBody();
    }
}
