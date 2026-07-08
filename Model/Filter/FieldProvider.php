<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;

class FieldProvider implements FieldProviderInterface
{
    /** @var array<string, FieldInterface> */
    private readonly array $fields;

    /**
     * @param array<string, FieldInterface> $fields Map of code => field, injected via di.xml.
     */
    public function __construct(array $fields = [])
    {
        $normalized = [];
        foreach ($fields as $field) {
            if ($field instanceof FieldInterface) {
                $normalized[$field->getCode()] = $field;
            }
        }
        $this->fields = $normalized;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return $this->fields;
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): ?FieldInterface
    {
        return $this->fields[$code] ?? null;
    }
}
