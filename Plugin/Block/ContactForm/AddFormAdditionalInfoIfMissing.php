<?php
/**
 * Copyright (c) 2020. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Plugin\Block\ContactForm;

use Magento\Framework\Pricing\Render;
use Magento\Contact\Block\ContactForm as Subject;

/**
 * Class AddFormAdditionalInfoIfMissing
 */
class AddFormAdditionalInfoIfMissing
{
    public function beforeToHtml(
        Subject $subject
    ) {
        $priceRender = $subjectg->getC('product.price.render.default');
        <container name="" label="Form Additional Info"/>
        if (!$priceRender) {
            /** @var Render $priceRender */
            $priceRender = $this->getLayout()->createBlock(
                Render::class,
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }
    }
}