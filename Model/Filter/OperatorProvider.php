<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;

class OperatorProvider implements OperatorProviderInterface
{
    /** @var array<string, OperatorInterface> */
    private readonly array $operators;

    /**
     * @param array<string, OperatorInterface> $operators Map of code => operator, injected via di.xml.
     */
    public function __construct(array $operators = [])
    {
        $normalized = [];
        foreach ($operators as $operator) {
            if ($operator instanceof OperatorInterface) {
                $normalized[$operator->getCode()] = $operator;
            }
        }
        $this->operators = $normalized;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return $this->operators;
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): ?OperatorInterface
    {
        return $this->operators[$code] ?? null;
    }
}
