Google Invisible Captcha v3 for magento 2

[![Latest Stable Version](https://poser.pugx.org/scriptua/magento2-invisible-captcha/v/stable)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![Total Downloads](https://poser.pugx.org/scriptua/magento2-invisible-captcha/downloads)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![Latest Unstable Version](https://poser.pugx.org/scriptua/magento2-invisible-captcha/v/unstable)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)
[![License](https://poser.pugx.org/scriptua/magento2-invisible-captcha/license)](https://packagist.org/packages/scriptua/magento2-invisible-captcha)

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


# Develop usage
````
require([
    'jquery',
    'reCaptcha'
], function ($) {
    $(form).reCaptcha({
        'callback': function (t, token) {
            if(t.$parentForm.validation() && t.$parentForm.validation('isValid')) {
                t.tokenField.val(token);
                t.$parentForm.submit();
            }
        }
    });
});
````
