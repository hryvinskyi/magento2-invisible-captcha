Google Invisible Captcha v3 for magento 2

![Packagist](https://img.shields.io/packagist/v/scriptua/magento2-invisible-captcha.svg)
![Packagist](https://img.shields.io/packagist/dt/scriptua/magento2-invisible-captcha.svg)
[![Code Coverage](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/?branch=master)
![Packagist](https://img.shields.io/packagist/vpre/scriptua/magento2-invisible-captcha.svg)
![Packagist](https://img.shields.io/packagist/l/scriptua/magento2-invisible-captcha.svg)

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
