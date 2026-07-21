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
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountryResolverInterface;
use Magento\Framework\Phrase;

/**
 * Country of the current visitor, resolved via the admin-selected geolocation
 * source. Values are uppercase ISO 3166-1 alpha-2 codes (`UA`, `DE`); `T1`
 * signals Tor when the source is Cloudflare. An unknown country resolves to the
 * empty string (mirroring {@see ClientIp}), so negative operators such as
 * `does not equal` / `not in list` match traffic whose country could not be
 * determined.
 */
class Country implements FieldInterface, FieldValueHintInterface
{
    /**
     * @param CountryResolverInterface $countryResolver
     */
    public function __construct(
        private readonly CountryResolverInterface $countryResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'country';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Country (ISO 3166-1 alpha-2)');
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
        return $this->countryResolver->getCountryCode() ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getValueHint(): array
    {
        return [
            'pattern' => '^[A-Za-z0-9]{2}$',
            'message' => (string)__('Enter a 2-letter ISO 3166-1 country code, e.g. UA.'),
            'placeholder' => 'UA',
        ];
    }
}
