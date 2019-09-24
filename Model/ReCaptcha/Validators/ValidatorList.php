<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Validators;

/**
 * Class ValidatorList
 */
class ValidatorList
{
    /**
     * @var ValidatorInterface[]
     */
    private $entityTypes = [];

    /**
     * EntityList constructor.
     *
     * @param ValidatorInterface[] $entityTypes
     */
    public function __construct(
        $entityTypes = []
    ) {
        $this->entityTypes = $entityTypes;
    }

    /**
     * Retrieve list of entities
     *
     * @return ValidatorInterface[]
     */
    public function getList(): array
    {
        return $this->entityTypes;
    }

    /**
     * @param string $code
     *
     * @return ValidatorInterface
     */
    public function getEntityByCode(string $code): ValidatorInterface
    {
        return $this->entityTypes[$code];
    }
}
