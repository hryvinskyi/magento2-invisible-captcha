/*
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

define(['ko'], function (ko) {
    'use strict';
    return {
        ids: ko.observableArray([]),
        captchaList: ko.observableArray([]),
        tokenFields: ko.observableArray([])
    };
});