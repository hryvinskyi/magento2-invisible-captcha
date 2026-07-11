<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt;

/**
 * A robots.txt group: one or more stacked `User-agent` lines followed by the
 * Allow / Disallow rules that apply to those agents.
 *
 * A group with an empty rule list is still meaningful — it declares that the
 * named agents are allowed everywhere, shadowing any broader `*` group.
 */
interface GroupInterface
{
    /**
     * Lower-cased user-agent tokens the group was declared for ("*" for the
     * catch-all group).
     *
     * @return string[]
     */
    public function getUserAgents(): array;

    /**
     * The group's rules in file order.
     *
     * @return RuleInterface[]
     */
    public function getRules(): array;
}
