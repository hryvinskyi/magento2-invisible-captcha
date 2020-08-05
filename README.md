# Google Invisible Captcha v3 for magento 2

[![Latest Stable Version](https://poser.pugx.org/scriptua/magento2-invisible-captcha/v/stable)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![Total Downloads](https://poser.pugx.org/scriptua/magento2-invisible-captcha/downloads)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![PayPal donate button](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=legionerblack%40yandex%2eru&lc=UA&item_name=Magento%202%20Invisible%20Captcha&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted "Donate once-off to this project using Paypal")
[![Latest Unstable Version](https://poser.pugx.org/scriptua/magento2-invisible-captcha/v/unstable)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![License](https://poser.pugx.org/scriptua/magento2-invisible-captcha/license)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-invisible-captcha/build-status/master)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-invisible-captcha.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-invisible-captcha?ref=badge_shield)

Module version 2.0.\*||2.1.\* support Magento 2.3.*  
Module version 1.0.* support Magento 2.1.\*||2.2.\*

## Features
1. Lazy Load, google page speed improvements
2. Easy to add captcha to your custom form
3. AJAX forms supported
4. Knockout forms supported

## Frontend Forms
 * Login
 * Register
 * Forgot password
 * Contact
 * Newsletter
 * Send to Friend
 

## Backend Forms
 * Login
 * Forgot password

## Installation Guide
### Install by composer
```
composer require scriptua/magento2-invisible-captcha
bin/magento module:enable Hryvinskyi_Base
bin/magento module:enable Hryvinskyi_InvisibleCaptcha
bin/magento setup:upgrade
```

### Install download package
1. Download module https://github.com/hryvinskyi/magento2-base [Link](https://github.com/hryvinskyi/magento2-base/archive/v1.1.2.zip)
2. Download this module [Link](https://github.com/hryvinskyi/magento2-invisible-captcha/archive/2.0.4.zip)
3. Unzip two modules in the folder app\code\Hryvinskyi\Base and app\code\Hryvinskyi\InvisibleCaptcha
4. Run commands:

    ```
    bin/magento module:enable Hryvinskyi_Base
    bin/magento module:enable Hryvinskyi_InvisibleCaptcha
    bin/magento setup:upgrade
    ```
5. Configure module in admin panel

### Command-line:

```
php bin/magento hryvinskyi:invisible-captcha:disable <area> --website_id=<website_id>
```

This command will disable invisible captcha for the area and/or website_id.

 * area = [global, frontend, adminhtml]
 * website_id = ID Website

# General Settings

[![Configuration](https://github.com/hryvinskyi/magento2-invisible-captcha/blob/2.1.4/screenshots/admin_configuration.png)](https://github.com/hryvinskyi/magento2-invisible-captcha/blob/2.1.0/screenshots/admin_configuration.png)


## License
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-invisible-captcha.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-invisible-captcha?ref=badge_large)