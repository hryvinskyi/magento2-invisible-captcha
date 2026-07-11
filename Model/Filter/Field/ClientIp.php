<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldValueHintInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

class ClientIp implements FieldInterface, FieldValueHintInterface
{
    /**
     * Server headers checked when resolving the client IP, in priority order.
     */
    private const CLIENT_IP_HEADERS = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'client_ip';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Client IP');
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        foreach (self::CLIENT_IP_HEADERS as $header) {
            $value = $this->request->getServer($header);
            if (empty($value)) {
                continue;
            }

            $ip = (string)$value;
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function getValueHint(): array
    {
        return [
            'pattern' => '^(\\d{1,3}(\\.\\d{1,3}){3}|[0-9A-Fa-f:]*:[0-9A-Fa-f:]*)$',
            'message' => (string)__('Enter a valid IPv4 or IPv6 address.'),
            'placeholder' => '203.0.113.10',
        ];
    }
}
