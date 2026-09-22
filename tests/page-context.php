<?php

// Run against a Contao 5.7 installation:
// php tests/page-context.php /path/to/contao/vendor/autoload.php
require $argv[1] ?? __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../src/EventListener/AjaxPageContextListener.php';

use Contao\CoreBundle\Routing\PageFinder;
use Contao\PageModel;
use DVC\AsyncPagination\Controller\AjaxEndpointController;
use DVC\AsyncPagination\EventListener\AjaxPageContextListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function page(int $id, string $language, string $type = 'regular'): PageModel
{
    return new class($id, $language, $type) extends PageModel {
        public function __construct(int $id, string $language, string $type)
        {
            $this->arrData = ['id' => $id, 'rootLanguage' => $language, 'language' => $language, 'type' => $type];
        }

        public function loadDetails()
        {
            return $this;
        }
    };
}

$root = page(1, 'de');
$blog = page(4, 'de');
$english = page(5, 'en');
$matcher = new class($blog, $english) implements RequestMatcherInterface {
    public array $matched = [];

    public function __construct(private PageModel $blog, private PageModel $english) {}

    public function matchRequest(Request $request): array
    {
        $this->matched[] = $request->getUri();

        return match ($request->getPathInfo()) {
            '/blog' => ['pageModel' => $this->blog],
            '/en/blog' => ['pageModel' => $this->english],
            '/custom-404' => ['pageModel' => page(14, 'de', 'error_404')],
            '/_dvc/ajax/module/14' => ['_route' => AjaxEndpointController::class],
            default => throw new ResourceNotFoundException(),
        };
    }
};
$finder = new class($root) extends PageFinder {
    public function __construct(private PageModel $root) {}

    public function findRootPageForRequest(Request $request): ?PageModel
    {
        return $this->root;
    }
};
$kernel = new class implements HttpKernelInterface {
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        throw new LogicException('Context resolution must never dispatch another HTTP request.');
    }
};
$listener = new AjaxPageContextListener($matcher, $finder);

$cases = [
    ['explicit German page', ['_dvc_page' => '/blog?category=messe'], null, 4, 'de'],
    ['explicit English page', ['_dvc_page' => '/en/blog'], null, 5, 'en'],
    ['legacy Referer', [], 'https://example.test/blog', 4, 'de'],
    ['legacy no Referer', [], null, 1, 'de'],
    ['foreign Referer ignored', [], 'https://foreign.test/en/blog', 1, 'de'],
    ['different port ignored', [], 'https://example.test:444/en/blog', 1, 'de'],
    ['obsolete Referer', [], 'https://example.test/missing', 1, 'de'],
    ['explicit page wins', ['_dvc_page' => '/en/blog'], 'https://example.test/blog', 5, 'en'],
];
foreach ($cases as [$name, $query, $referer, $id, $locale]) {
    $request = Request::create('https://example.test/_dvc/ajax/module/14', 'GET', $query);
    $request->attributes->set('_route', AjaxEndpointController::class);
    if ($referer !== null) {
        $request->headers->set('Referer', $referer);
    }
    $listener(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
    check((int) $request->attributes->get('pageModel')->id === $id, "$name: page");
    check($request->attributes->get('_locale') === $locale, "$name: locale");
    check($request->attributes->get('_dvc_context_from_headers') === !isset($query['_dvc_page']), "$name: cache context");
    echo "PASS $name\n";
}
foreach (['https://foreign.test/blog' => 400, '//foreign.test/blog' => 400, '/\\foreign.test/blog' => 400, '' => 400, '/missing' => 404, '/custom-404' => 404, '/_dvc/ajax/module/14' => 404] as $source => $status) {
    $request = Request::create('https://example.test/_dvc/ajax/module/14', 'GET', ['_dvc_page' => $source]);
    $request->attributes->set('_route', AjaxEndpointController::class);
    try {
        $listener(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        throw new RuntimeException("Invalid context accepted: $source");
    } catch (HttpExceptionInterface $e) {
        check($e->getStatusCode() === $status, "Invalid context returned wrong status: $source");
    }
}
$request = Request::create('https://example.test/blog');
$listener(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
check(!$request->attributes->has('pageModel'), 'Unrelated requests must remain unchanged.');
check(!array_filter($matcher->matched, fn ($url) => str_contains($url, 'foreign.test') || str_contains($url, ':444')), 'Foreign origins must not be matched.');
echo "PASS invalid contexts and unrelated requests\n";
