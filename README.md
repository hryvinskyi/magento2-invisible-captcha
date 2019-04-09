Google Invisible Captcha v3 for magento 2

[![Latest Stable Version](https://poser.pugx.org/scriptua/magento2-invisible-captcha/v/stable)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![Total Downloads](https://poser.pugx.org/scriptua/magento2-invisible-captcha/downloads)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![PayPal donate button](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=legionerblack%40yandex%2eru&lc=UA&item_name=Magento%202%20Invisible%20Captcha&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted "Donate once-off to this project using Paypal")
[![Latest Unstable Version](https://poser.pugx.org/scriptua/magento2-invisible-captcha/v/unstable)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![License](https://poser.pugx.org/scriptua/magento2-invisible-captcha/license)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/build-status/master)

Module version 2.0.* support Magento 2.3.*  
Module version 1.0.* support Magento 2.1.\*||2.2.\*

# Installation Guide
### Install by composer
````
composer require scriptua/magento2-invisible-captcha
bin/magento module:enable Hryvinskyi_Base
bin/magento module:enable Hryvinskyi_InvisibleCaptcha
bin/magento setup:upgrade
````
### Install download package
1. Download module https://github.com/hryvinskyi/magento2-base "Clone or download -> Download Zip" 
2. Download this module "Clone or download -> Download Zip"
3. Unzip two modules in the folder app\code\Hryvinskyi\Base and app\code\Hryvinskyi\InvisibleCaptcha
4. Run commands:

```
bin/magento module:enable Hryvinskyi_Base
bin/magento module:enable Hryvinskyi_InvisibleCaptcha
bin/magento setup:upgrade
```
5. Configure module in admin panel

# General Settings
To get the access to the 'Invisible Captcha' settings please go to
Stores -> Configuration -> Hryvinskyi Extensions -> Google Invisible Captcha and expand the General Settings section.

***Enable invisible captcha:*** Enable or disable the extension from here.  
***Site Key:*** Enter the site key you have got while registering for reCAPTCHA v3.  
***Secret Key:*** Enter the secret key you have got while registering for reCAPTCHA v3.  
***URLs to Enable:*** Enter the URLs to enable Google Invisible reCAPTCHA on.  
***Selectors for Forms:*** Add form selectors to enable Google Invisible reCAPTCHA on.

# Develop usage
````
require([
    'jquery',
    'reCaptcha'
], function ($) {
    $('form').reCaptcha();
});
````
