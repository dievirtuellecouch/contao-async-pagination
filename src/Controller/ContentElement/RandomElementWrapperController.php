<?php

namespace DVC\AsyncPagination\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(
    category: 'includes',
    nestedFragments: true,
)]
class RandomElementWrapperController extends AbstractContentElementController
{
    public const TYPE = 'random_element_wrapper';

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $elements = $template->get('nested_fragments') ?? [];

        if ([] === $elements) {
            $template->set('elements', []);

            return $template->getResponse();
        }

        $randomIndex = random_int(0, count($elements) - 1);

        $template->set('elements', array_slice($elements, $randomIndex, 1));

        return $template->getResponse();
    }
}
