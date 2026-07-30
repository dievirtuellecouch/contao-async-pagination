<?php

use DVC\AsyncPagination\Controller\ContentElement\AsyncDeferredLoadWrapperController;
use DVC\AsyncPagination\Controller\ContentElement\AsyncPaginationWrapperController;
use DVC\AsyncPagination\Controller\ContentElement\RandomElementWrapperController;

$GLOBALS['TL_LANG']['CTE'][AsyncDeferredLoadWrapperController::TYPE] = [
    'Asynchrones Nachladen',
    '',
];

$GLOBALS['TL_LANG']['CTE'][AsyncPaginationWrapperController::TYPE] = [
    'Asynchrone Paginierung',
    '',
];

$GLOBALS['TL_LANG']['CTE'][RandomElementWrapperController::TYPE] = [
    'Zufallselement',
    '',
];
