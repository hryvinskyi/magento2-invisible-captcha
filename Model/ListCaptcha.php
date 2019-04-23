<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

/**
 * Class ListCaptcha
 */
class ListCaptcha
{
    /**
     * @var CaptchaInterface[]
     */
    private $entityTypes = [];

    /**
     * EntityList constructor.
     *
     * @param CaptchaInterface[] $entityTypes
     */
    public function __construct(
        $entityTypes = []
    ) {
        $this->entityTypes = $entityTypes;
    }

    /**
     * Retrieve list of entities
     *
     * @return CaptchaInterface[]
     */
    public function getList(): array
    {
        return $this->entityTypes;
    }

    /**
     * @param string $action
     *
     * @return CaptchaInterface
     */
    public function getCaptchaByAction(string $action): CaptchaInterface
    {
        return $this->entityTypes[$action];
    }
}