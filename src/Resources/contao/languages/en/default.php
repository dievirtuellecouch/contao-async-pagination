<?php

use DVC\AsyncPagination\Controller\ContentElement\AsyncDeferredLoadWrapperController;
use DVC\AsyncPagination\Controller\ContentElement\AsyncPaginationWrapperController;
use DVC\AsyncPagination\Controller\ContentElement\RandomElementWrapperController;

$GLOBALS['TL_LANG']['CTE'][AsyncDeferredLoadWrapperController::TYPE] = [
    'Asynchronous deferred loading',
    '',
];

$GLOBALS['TL_LANG']['CTE'][AsyncPaginationWrapperController::TYPE] = [
    'Asynchronous pagination',
    '',
];

$GLOBALS['TL_LANG']['CTE'][RandomElementWrapperController::TYPE] = [
    'Asynchronous random element',
    '',
];
