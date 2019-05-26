<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\Base\Helper\ArrayHelper;

/**
 * Class FailureMessages
 */
class FailureMessages
{
    /**
     * @var array
     */
    private $errorMessages;

    /**
     * AbstractFailure constructor.
     *
     * @param array $errorMessages
     */
    public function __construct(
        array $errorMessages = []
    ) {
        $this->errorMessages = $errorMessages;
    }

    /**
     * @return array
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getErrorMessage(string $key): ?string
    {
        return ArrayHelper::getValue($this->getErrorMessages(), $key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasErrorMessage(string $key): bool
    {
        return ArrayHelper::keyExists($key, $this->getErrorMessages());
    }
}
