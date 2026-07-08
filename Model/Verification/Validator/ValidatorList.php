<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\Validator\ValidatorInterface;

/**
 * Ordered list of post-verification validators (DI-configured array).
 */
class ValidatorList
{
    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(
        private readonly array $validators = []
    ) {
    }

    /**
     * @return ValidatorInterface[]
     */
    public function getList(): array
    {
        return $this->validators;
    }
}
