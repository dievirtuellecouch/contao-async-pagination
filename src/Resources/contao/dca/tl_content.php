<?php

use DVC\AsyncPagination\Controller\ContentElement\AsyncDeferredLoadWrapperController;
use DVC\AsyncPagination\Controller\ContentElement\AsyncPaginationWrapperController;
use DVC\AsyncPagination\Controller\ContentElement\RandomElementWrapperController;

$GLOBALS['TL_DCA']['tl_content']['palettes'][AsyncDeferredLoadWrapperController::TYPE] =
    '{type_legend},type;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop'
;

$GLOBALS['TL_DCA']['tl_content']['palettes'][AsyncPaginationWrapperController::TYPE] =
    '{type_legend},type;{async_pagination_legend},asyncPaginationScrollToTop;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop'
;

$GLOBALS['TL_DCA']['tl_content']['fields']['asyncPaginationScrollToTop'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_content']['palettes'][RandomElementWrapperController::TYPE] =
    '{type_legend},type;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop'
;
