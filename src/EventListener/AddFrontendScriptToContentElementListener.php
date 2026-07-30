<?php

namespace DVC\AsyncPagination\EventListener;

use Contao\ContentElement;
use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use DVC\AsyncPagination\Controller\ContentElement\AsyncDeferredLoadWrapperController;
use DVC\AsyncPagination\Controller\ContentElement\AsyncPaginationWrapperController;

#[AsHook('getContentElement')]
class AddFrontendScriptToContentElementListener
{
    public function __invoke(ContentModel $contentModel, string $buffer): string
    {
        if (isset($this->getSupportedElementTypes()[$contentModel->type])) {
            foreach ($this->getSupportedElementTypes()[$contentModel->type] as $asset) {
                $GLOBALS['TL_HEAD'][] = \sprintf('<script src="%s?v=%s" defer></script>', $asset['path'], $asset['version']);
            }
        }

        return $buffer;
    }

    private function getSupportedElementTypes(): array
    {
        return [
            AsyncPaginationWrapperController::TYPE => [
                [
                    'path' => 'bundles/asyncpagination/async-pagination.js',
                    'version' => '5.7.0',
                ],
            ],
            AsyncDeferredLoadWrapperController::TYPE => [
                [
                    'path' => 'bundles/asyncpagination/async-load.js',
                    'version' => '5.7.0',
                ],
            ],
        ];
    }
}
