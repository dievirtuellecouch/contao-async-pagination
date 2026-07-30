<?php

namespace DVC\AsyncPagination\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(
    category: 'includes',
    nestedFragments: true,
)]
class AsyncDeferredLoadWrapperController extends AbstractContentElementController
{
    public const TYPE = 'async_deferred_load_wrapper';

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $elements = [];

        foreach ($template->get('nested_fragments') as $reference) {
            $nestedContentModel = $reference->getContentModel();

            $elements[] = [
                'attributes' => [
                    'data-element' => 'target',
                    'data-type' => 'content',
                    'data-id' => $nestedContentModel,
                ],
                'reference' => $reference,
            ];
        }

        $template->set('elements', $elements);

        return $template->getResponse();
    }
}
