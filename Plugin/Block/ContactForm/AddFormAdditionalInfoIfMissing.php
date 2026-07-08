<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Plugin\Block\ContactForm;

use Hryvinskyi\InvisibleCaptcha\Api\EnablementInterface;
use Hryvinskyi\InvisibleCaptcha\Block\Captcha;
use Magento\Contact\Block\ContactForm as Subject;
use Psr\Log\LoggerInterface;

/**
 * Ensures the contact form has a "form.additional.info" container hosting the
 * captcha block, even on themes that don't declare it in layout XML.
 */
class AddFormAdditionalInfoIfMissing
{
    /**
     * @param EnablementInterface $contactEnablement
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly EnablementInterface $contactEnablement,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Subject $subject
     * @return void
     */
    public function beforeToHtml(Subject $subject)
    {
        try {
            $children = $subject->getChildNames();

            if (in_array('form.additional.info', $children, true) === false
                && $this->contactEnablement->isEnabled() === true
            ) {
                $subject->getLayout()->addContainer(
                    'form.additional.info',
                    'Form Additional Info',
                    [],
                    $subject->getNameInLayout(),
                    'form.additional.info'
                );

                $block = $subject->getLayout()->createBlock(
                    Captcha::class,
                    $subject->getNameInLayout() . '.invisible.recaptcha',
                    [
                        'data' => [
                            'template' => 'Hryvinskyi_InvisibleCaptcha::captcha.phtml',
                            'jsLayout' => [
                                'components' => [
                                    'invisible-captcha' => [
                                        'component' => 'Hryvinskyi_InvisibleCaptcha/js/invisible-captcha',
                                        'action' => 'contact',
                                        'captchaId' => 'contact',
                                    ],
                                ],
                            ],
                        ],
                    ]
                );
                $subject->setChild('form.additional.info', $block);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage(), $exception->getTrace());
        }
    }
}
