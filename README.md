Magento 2 Product QuickView Module

# Installation Guide
````
composer require scriptua/magento2-invisible-captcha
bin/magento module:enable Script_InvisibleCaptcha
bin/magento setup:upgrade
````

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