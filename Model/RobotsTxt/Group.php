<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\GroupInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\RuleInterface;

class Group implements GroupInterface
{
    /** @var string[] */
    private readonly array $userAgents;

    /** @var RuleInterface[] */
    private readonly array $rules;

    /**
     * @param string[] $userAgents
     * @param RuleInterface[] $rules
     */
    public function __construct(array $userAgents = [], array $rules = [])
    {
        $this->userAgents = array_values(array_filter(
            $userAgents,
            static fn ($agent): bool => is_string($agent) && $agent !== ''
        ));
        $this->rules = array_values(array_filter(
            $rules,
            static fn ($rule): bool => $rule instanceof RuleInterface
        ));
    }

    /**
     * @inheritDoc
     */
    public function getUserAgents(): array
    {
        return $this->userAgents;
    }

    /**
     * @inheritDoc
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
