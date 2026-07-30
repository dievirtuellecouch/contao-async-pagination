<?php

namespace DVC\AsyncPagination\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(
    category: 'includes',
    nestedFragments: ['allowedTypes' => ['module']]
)]
class AsyncPaginationWrapperController extends AbstractContentElementController
{
    public const TYPE = 'async_pagination_wrapper';

    private array $targetFrontendModelTypes = ['list', 'reader', 'archive'];

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $elements = [];

        foreach ($template->get('nested_fragments') as $reference) {
            $nestedContentModel = $reference->getContentModel();
            $nestedModuleModel = null;

            if (!$nestedContentModel instanceof ContentModel) {
                $nestedContentModel = $this->framework->getAdapter(ContentModel::class)->findById($nestedContentModel);
            }

            if ($nestedContentModel->type == 'module') {
                $nestedModuleModel = $this->framework->getAdapter(ModuleModel::class)->findById($nestedContentModel->module);
            }

            $elements[] = [
                'attributes' => [
                    'data-element' => $this->getIdentifierForModel($nestedModuleModel ?? $nestedContentModel),
                    'data-type' => $this->getTypeForModel($nestedModuleModel ?? $nestedContentModel),
                    'data-id' => $nestedModuleModel?->id ?? $nestedContentModel->id,
                ],
                'reference' => $reference,
            ];
        }

        $template->set('elements', $elements);

        return $template->getResponse();
    }

    private function getTypeForModel(ContentModel|ModuleModel $model): string
    {
        if ($model instanceof ModuleModel) {
            return 'module';
        }

        return 'content';
    }

    private function getIdentifierForModel(ContentModel|ModuleModel $model): string
    {
        foreach ($this->getTargetFrontendModelTypes() as $targetType) {
            if (!is_string($targetType)) {
                continue;
            }

            if (str_contains($model->type, $targetType)) {
                return 'target';
            }
        }

        return 'filter';
    }

    public function addTargetFrontendModelTypes(array $additionalTargetFrontendModelTypes): void
    {
        $this->targetFrontendModelTypes = array_unique(array_merge($this->targetFrontendModelTypes, $additionalTargetFrontendModelTypes));
    }

    private function getTargetFrontendModelTypes(): array
    {
        return $this->targetFrontendModelTypes;
    }
}
