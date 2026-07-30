<?php

namespace DVC\AsyncPagination\Controller;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/_dvc/ajax/{moduleType}/{typeOrId}',
    name: AjaxEndpointController::class,
    defaults:[
        '_scope' => 'frontend',
        '_token_check' => false,
    ]
)]
class AjaxEndpointController
{
    private ?int $sharedMaxAge = null;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly EntityCacheTags $entityCacheTags,
    ) {
    }

    public function __invoke(Request $request, string $moduleType, int|string $typeOrId): JsonResponse
    {
        $this->framework->initialize();
        $uncached = $request->query->getBoolean('uncached', false);

        switch ($moduleType) {
            case 'module':
                $html = $this->renderModule($typeOrId);
                break;

            case 'content':
                $html = $this->renderContent($typeOrId);
                break;

            default:
                $html = null;
                break;
        }

        if ($html === null) {
            throw new NotFoundHttpException();
        }

        $html = $this->cleanHtml($html);

        $response = new JsonResponse([
            'html' => $html,
        ]);

        if ($this->getSharedMaxAge() && $uncached !== true) {
            $response->setSharedMaxAge($this->getSharedMaxAge());
        }

    	return $response;
    }

    private function renderModule(int|string $typeOrId, array $data = []): ?string
    {
        $model = $this->getModel(ModuleModel::class, $typeOrId, $data);

        if ($model === null) {
            return null;
        }

        $this->entityCacheTags->tagWith($model);

        return $this->framework->getAdapter(Controller::class)->getFrontendModule($model);
    }

    private function renderContent(int|string $typeOrId, array $data = []): ?string
    {
        $model = $this->getModel(ContentModel::class, $typeOrId, $data);

        if ($model === null) {
            return null;
        }

        $this->entityCacheTags->tagWith($model);

        return $this->framework->getAdapter(Controller::class)->getContentElement($model);
    }

    private function getModel(string $class, int|string $typeOrId, array $data = []): ContentModel|ModuleModel|null
    {
        if (is_numeric($typeOrId)) {
            /** @var Adapter<ContentModel|ModuleModel> $adapter */
            $adapter = $this->framework->getAdapter($class);
            $model = $adapter->findById($typeOrId);
        } else {
            $model = $this->framework->createInstance($class);
            $model->type = $typeOrId;
        }

        foreach ($data as $k => $v) {
            if (null !== $v && !\is_scalar($v)) {
                $v = serialize($v);
            }

            $model->$k = $v;
        }

        $model?->preventSaving(false);

        return $model;
    }

    private function cleanHtml(string $html): string
    {
        $html = str_replace('<!-- indexer::stop -->', '', $html);
        $html = str_replace('<!-- indexer::continue -->', '', $html);

        return trim($html);
    }

    public function setSharedMaxAge(?int $sharedMaxAge): void
    {
        $this->sharedMaxAge = $sharedMaxAge;
    }

    private function getSharedMaxAge(): ?int
    {
        return $this->sharedMaxAge;
    }
}
