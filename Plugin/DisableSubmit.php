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
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;

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
     * MergeJson constructor.
     *
     * @param RequestHttp $request
     * @param General $generalConfig
     */
    public function __construct(
        RequestHttp $request,
        General $generalConfig
    ) {
        $this->request = $request;
        $this->generalConfig = $generalConfig;
    }

    /**
     * @param ResultInterface $subject
     * @param Closure $proceed
     * @param Http $response
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

        try {
            $crawler = new Crawler($html);
            $crawler->filter('[data-hryvinskyi-recaptcha="default"]')->each(function ($node, $i) {
                /** @var $node Crawler */
                $form = $this->closest($node, 'form');
                $form->getNode(0)->setAttribute('onsubmit', 'return false;' . $form->attr('onsubmit'));
                $form->getNode(0)
                    ->setAttribute('class', $form->attr('class') . ' hryvinskyi-recaptcha-disabled-submit');
            });

            $crawler->filter('[data-hryvinskyi-recaptcha="target"]')->each(function ($node) use ($crawler) {
                /** @var $node Crawler */
                $crawler->filter($node->attr('data-hryvinskyi-recaptcha-target'))->each(function ($form) {
                    $form->getNode(0)
                        ->setAttribute('onsubmit', 'return false;' . $form->attr('onsubmit'));
                    $form->getNode(0)
                        ->setAttribute('class', $form->attr('class') . ' hryvinskyi-recaptcha-disabled-submit');
                });
            });
            $response->setBody($crawler->outerHtml());
        } catch (\InvalidArgumentException $exception) {
            $response->setBody($html);
        }

        return $result;
    }

    /**
     * @param Crawler $crawler
     * @param $cssSelector
     *
     * @return Crawler
     */
    private function closest(Crawler $crawler, $cssSelector)
    {
        $xpath = (new CssSelectorConverter())->toXPath($cssSelector, './');

        $closest = null;

        for (
            $domNode = $crawler->getNode(0);
            $domNode !== null && $domNode->nodeType === XML_ELEMENT_NODE;
            $domNode = $domNode->parentNode
        ) {
            $subcrawler = new Crawler($domNode);
            $subcrawlerFiltered = $subcrawler->filterXPath($xpath);

            if (count($subcrawlerFiltered) > 0) {
                $closest = $subcrawlerFiltered;
                break;
            }
        }

        if ($closest === null) {
            $closest = new Crawler();
        }

        return $closest;
    }
}