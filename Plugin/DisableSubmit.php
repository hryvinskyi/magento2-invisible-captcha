<?php
/**
 * Copyright (c) 2020. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Plugin;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;
use voku\helper\SimpleHtmlDomInterface;

/**
 * Class DisableSubmit
 */
class DisableSubmit
{
    /**
     * @var RequestHttp
     */
    private $request;

    /**
     * @var General
     */
    private $generalConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MergeJson constructor.
     *
     * @param RequestHttp $request
     * @param General $generalConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestHttp $request,
        General $generalConfig,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->generalConfig = $generalConfig;
        $this->logger = $logger;
    }

    /**
     * @param ResultInterface $subject
     * @param \Closure $proceed
     * @param ResponseInterface $response
     *
     * @return string
     */
    public function aroundRenderResult(
        ResultInterface $subject,
        \Closure $proceed,
        ResponseInterface $response
    ) {
        $result = $proceed($response);

        if (
            PHP_SAPI === 'cli' ||
            $this->request->isXmlHttpRequest() === true ||
            $this->generalConfig->isDisabledSubmitForm() === false
        ) {
            return $result;
        }

        $html = $response->getBody();
        $dom = new HtmlDomParser();

        $dom->overwriteSpecialScriptTags([
            'text/html',
            'text/x-magento-template',
            'text/x-custom-template',
            'text/x-handlebars-template',
        ]);

        $dom = $dom->loadHtml($html);

        try {
            $elements = $dom->findMultiOrFalse('[data-hryvinskyi-recaptcha="default"]');

            if ($elements !== false) {
                foreach ($elements as $element) {
                    $form = $this->closest($element, 'form');

                    $form->setAttribute('onsubmit', 'return false;' .
                        $form->getAttribute('onsubmit'));
                    $form->setAttribute('class', $form->getAttribute('class') .
                        ' hryvinskyi-recaptcha-disabled-submit');
                }
            }

            $elements = $dom->findMultiOrFalse('[data-hryvinskyi-recaptcha="target"]');

            if ($elements !== false) {
                foreach ($elements as $element) {
                    $target = $dom->findOneOrFalse($element->getAttribute('data-hryvinskyi-recaptcha-target'));
                    if ($target !== false) {
                        $target->setAttribute('onsubmit', 'return false;' .
                            $target->getAttribute('onsubmit'));
                        $target->setAttribute('class', $target->getAttribute('class') .
                            ' hryvinskyi-recaptcha-disabled-submit');
                    }
                }
            }

            $response->setBody((string)$dom);
        } catch (\Throwable $e) {
            $response->setBody($html);
            $this->logger->critical($e->getMessage());
        }

        return $result;
    }

    /**
     * Return first parents (heading toward the document root) of the Element that matches the provided selector.
     *
     * @param SimpleHtmlDomInterface $element
     * @param string $selector
     */
    public function closest($element, string $selector)
    {
        $domNode = $element->getNode();

        while (XML_ELEMENT_NODE === $domNode->nodeType) {
            $symfonyNode = new Crawler($domNode);
            $domNode = new SimpleHtmlDom($domNode);

            if ($symfonyNode->matches($selector)) {
                return $domNode;
            }

            $domNode = $symfonyNode->getNode(0)->parentNode;
        }

        return $domNode;
    }
}
