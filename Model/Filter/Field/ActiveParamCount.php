<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldValueHintInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

class ActiveParamCount implements FieldInterface, FieldValueHintInterface
{
    /**
     * @param RequestInterface $request
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'active_param_count';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Active Param Count (non-ignored)');
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::TYPE_NUMERIC;
    }

    /**
     * @inheritDoc
     *
     * Counts request params that are not on the "Ignored filter params"
     * config list and have a non-empty value, matching the same notion of
     * "active filter param" used elsewhere in the module.
     */
    public function getValue(): int
    {
        $ignored = $this->config->getLayeredNavIgnoredParams();

        $count = 0;
        foreach ($this->request->getParams() as $key => $value) {
            if ($value === '' || $value === null || $value === []) {
                continue;
            }
            if (in_array($key, $ignored, true)) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    /**
     * @inheritDoc
     */
    public function getValueHint(): array
    {
        return [
            'placeholder' => '3',
        ];
    }
}
