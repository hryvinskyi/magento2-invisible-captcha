<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Plugin\Block\ContactForm;

use Hryvinskyi\InvisibleCaptcha\Api\EnablementInterface;
use Hryvinskyi\InvisibleCaptcha\Block\Captcha;
use Hryvinskyi\InvisibleCaptcha\Plugin\Block\ContactForm\AddFormAdditionalInfoIfMissing;
use Magento\Contact\Block\ContactForm as Subject;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AddFormAdditionalInfoIfMissingTest extends TestCase
{
    /** @var EnablementInterface&MockObject */
    private EnablementInterface $contactEnablement;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    private AddFormAdditionalInfoIfMissing $plugin;

    protected function setUp(): void
    {
        $this->contactEnablement = $this->createMock(EnablementInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->plugin = new AddFormAdditionalInfoIfMissing(
            $this->contactEnablement,
            $this->logger
        );
    }

    public function testAddsContainerAndBlockWhenEnabledAndChildMissing(): void
    {
        $this->contactEnablement->method('isEnabled')->willReturn(true);

        $block = $this->createMock(BlockInterface::class);
        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects($this->once())
            ->method('addContainer')
            ->with('form.additional.info', 'Form Additional Info', [], 'contactForm', 'form.additional.info');
        $layout->expects($this->once())
            ->method('createBlock')
            ->with(Captcha::class, 'contactForm.invisible.recaptcha', $this->anything())
            ->willReturn($block);

        $subject = $this->createMock(Subject::class);
        $subject->method('getChildNames')->willReturn([]);
        $subject->method('getNameInLayout')->willReturn('contactForm');
        $subject->method('getLayout')->willReturn($layout);
        $subject->expects($this->once())
            ->method('setChild')
            ->with('form.additional.info', $block);

        $this->logger->expects($this->never())->method('critical');

        $this->plugin->beforeToHtml($subject);
    }

    public function testDoesNothingWhenChildAlreadyPresent(): void
    {
        $this->contactEnablement->expects($this->never())->method('isEnabled');

        $subject = $this->createMock(Subject::class);
        $subject->method('getChildNames')->willReturn(['form.additional.info']);
        $subject->expects($this->never())->method('getLayout');
        $subject->expects($this->never())->method('setChild');

        $this->plugin->beforeToHtml($subject);
    }

    public function testDoesNothingWhenDisabled(): void
    {
        $this->contactEnablement->method('isEnabled')->willReturn(false);

        $subject = $this->createMock(Subject::class);
        $subject->method('getChildNames')->willReturn([]);
        $subject->expects($this->never())->method('getLayout');
        $subject->expects($this->never())->method('setChild');

        $this->plugin->beforeToHtml($subject);
    }

    public function testExceptionIsLoggedAndSwallowed(): void
    {
        $subject = $this->createMock(Subject::class);
        $subject->method('getChildNames')
            ->willThrowException(new \RuntimeException('boom'));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('boom', $this->isType('array'));

        // Must not bubble up.
        $this->plugin->beforeToHtml($subject);
    }
}
