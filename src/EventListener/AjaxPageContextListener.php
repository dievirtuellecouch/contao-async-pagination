<?php

namespace DVC\AsyncPagination\EventListener;

use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\PageModel;
use DVC\AsyncPagination\Controller\AjaxEndpointController;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

// After routing (32), before Contao/Symfony locale listeners (20/16) and
// Contao's page access (7) and maintenance (6) checks.
#[AsEventListener(priority: 24)]
class AjaxPageContextListener
{
    public function __construct(
        private readonly RequestMatcherInterface $router,
        private readonly PageFinder $pageFinder,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->attributes->get('_route') !== AjaxEndpointController::class) {
            return;
        }

        $explicit = $request->query->has('_dvc_page');
        $source = $explicit ? $request->query->get('_dvc_page') : $request->headers->get('Referer');
        $page = null;

        if ($explicit && (!is_string($source) || !str_starts_with($source, '/') || str_starts_with($source, '//') || preg_match('/[\\\\\x00-\x20]/', $source))) {
            throw new BadRequestHttpException('The page context must be a local absolute path.');
        }

        if ($source) {
            $context = Request::create($explicit ? $request->getSchemeAndHttpHost().$source : $source);

            // Never resolve a foreign website's context. Matching is local routing,
            // not an HTTP request to the supplied URL.
            if ($context->getSchemeAndHttpHost() === $request->getSchemeAndHttpHost()) {
                try {
                    $attributes = $this->router->matchRequest($context);
                    $page = $attributes['pageModel'] ?? null;

                    // Contao can match an error page instead of throwing for a
                    // missing/unpublished URL. It is not the requested context.
                    if ($page instanceof PageModel && in_array($page->type, ['error_401', 'error_403', 'error_404'], true)) {
                        $page = null;
                    }
                } catch (ExceptionInterface) {
                    // Legacy clients may omit Referer or send an obsolete URL.
                }
            }
        }

        if (!$page instanceof PageModel) {
            if ($explicit) {
                throw new NotFoundHttpException('The page context was not found.');
            }

            // Keep old clients/templates working without the new query parameter.
            $page = $this->pageFinder->findRootPageForRequest($request);
        }

        if ($page instanceof PageModel) {
            $page->loadDetails();
            $request->attributes->set('pageModel', $page);
            $request->attributes->set('_locale', LocaleUtil::formatAsLocale($page->rootLanguage ?: $page->language));
        }

        $request->attributes->set('_dvc_context_from_headers', !$explicit);
    }
}
