/*
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

define(['ko'], function (ko) {
    'use strict';

    return {
        isApiLoad: ko.observable(false),
        isApiLoaded: ko.observable(false),
        initializeForms: ko.observableArray([]),
        initializedForms: ko.observableArray([])
    };
});