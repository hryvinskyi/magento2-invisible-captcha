<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure\RedirectUrlInterface;
use Magento\Framework\App\Response\RedirectInterface;

class RefererProvider implements RedirectUrlInterface
{
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * RefererProvider constructor.
     *
     * @param RedirectInterface $redirect
     */
    public function __construct(
        RedirectInterface $redirect
    ) {
        $this->redirect = $redirect;
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(): string
    {
        return $this->redirect->getRedirectUrl();
    }
}