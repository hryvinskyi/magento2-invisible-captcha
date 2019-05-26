<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Response;

/**
 * Class AbstractFailure
 */
abstract class AbstractFailure implements FailureInterface
{
    /**
     * @var FailureMessages
     */
    private $failureMessages;

    /**
     * AbstractFailure constructor.
     *
     * @param FailureMessages $failureMessages
     */
    public function __construct(
        FailureMessages $failureMessages
    ) {
        $this->failureMessages = $failureMessages;
    }

    /**
     * @param Response $verifyReCaptcha
     *
     * @return array
     */
    public function getMessages(Response $verifyReCaptcha): array
    {
        $return = [];

        $errorMessages = $this->failureMessages->getErrorMessages();
        $errorsCodes = array_keys($errorMessages);
        $errors = array_intersect($verifyReCaptcha->getErrorCodes(), $errorsCodes);

        foreach ($errors as $error) {
            if ($this->failureMessages->hasErrorMessage($error)) {
                $return[] = $this->failureMessages->getErrorMessage($error);
            }
        }

        return $return;
    }

    /**
     * @param Response $verifyReCaptcha
     *
     * @return string
     */
    public function getMessagesString(Response $verifyReCaptcha): string
    {
        return implode('<br>', $this->getMessages($verifyReCaptcha));
    }
}
