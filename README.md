Google Invisible Captcha v3 for magento 2

![Packagist](https://img.shields.io/packagist/v/scriptua/magento2-invisible-captcha.svg)
![Packagist](https://img.shields.io/packagist/dt/scriptua/magento2-invisible-captcha.svg)
[![Code Coverage](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/?branch=master)
![Packagist](https://img.shields.io/packagist/vpre/scriptua/magento2-invisible-captcha.svg)
![Packagist](https://img.shields.io/packagist/l/scriptua/magento2-invisible-captcha.svg)

# Installation Guide
````
composer require scriptua/magento2-invisible-captcha
bin/magento module:enable Hryvinskyi_InvisibleCaptcha
bin/magento setup:upgrade
````

# General Settings
To get the access to the 'Invisible Captcha' settings please go to
Stores -> Configuration -> Hryvinskyi Extensions -> Google Invisible Captcha and expand the General Settings section.

***Enable invisible captcha:*** Enable or disable the extension from here.  
***Site Key:*** Enter the site key you have got while registering for reCAPTCHA v3.  
***Secret Key:*** Enter the secret key you have got while registering for reCAPTCHA v3.  
***URLs to Enable:*** Enter the URLs to enable Google Invisible reCAPTCHA on.  
***Selectors for Forms:*** Add form selectors to enable Google Invisible reCAPTCHA on.
