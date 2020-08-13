<?php
/**
 * Copyright (c) 2020. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Plugin\Block\ContactForm;

use Hryvinskyi\InvisibleCaptcha\Model\Verify\Contact;
use Magento\Framework\Pricing\Render;
use Magento\Contact\Block\ContactForm as Subject;
use Psr\Log\LoggerInterface;

/**
 * Class AddFormAdditionalInfoIfMissing
 */
class AddFormAdditionalInfoIfMissing
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Contact
     */
    private $verifyContact;

    /**
     * AddFormAdditionalInfoIfMissing constructor.
     *
     * @param Contact $verifyContact
     * @param LoggerInterface $logger
     */
    public function __construct(
        Contact $verifyContact,
        LoggerInterface $logger
    ) {
        $this->verifyContact = $verifyContact;
        $this->logger = $logger;
    }

    public function beforeToHtml(
        Subject $subject
    ) {
        try {
            $childrens = $subject->getChildNames();

            if (in_array('form.additional.info', $childrens) === false && $this->verifyContact->verify() === true) {
                $subject->getLayout()->addContainer(
                    'form.additional.info',
                    'Form Additional Info',
                    [],
                    $subject->getNameInLayout(),
                    'form.additional.info'
                );


                $block = $subject->getLayout()->createBlock(
                    \Hryvinskyi\InvisibleCaptcha\Block\Captcha::class,
                    $subject->getNameInLayout() . '.invisible.recaptcha',
                    [
                        'jsLayout' => [
                            'components' => [
                                'invisible-captcha' => [
                                    'component' =>'Hryvinskyi_InvisibleCaptcha/js/invisible-captcha',
                                    'action' => 'contact',
                                    'captchaId' => 'contact'
                                ]
                            ]
                        ],
                        'data' => [
                            'template' => 'Hryvinskyi_InvisibleCaptcha::captcha.phtml'
                        ]
                    ]
                );
                $subject->setChild('form.additional.info', $block);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage(), $exception->getTrace());
        }
    }
}
