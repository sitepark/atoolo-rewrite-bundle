<?php

declare(strict_types=1);

namespace Atoolo\Rewrite\Test\Service;

use Atoolo\Resource\Service\PParameterService;
use Atoolo\Rewrite\Dto\Url;
use Atoolo\Rewrite\Dto\UrlRewriterHandlerContext;
use Atoolo\Rewrite\Service\SameNavigationUrlRewriteHandler;
use Atoolo\Rewrite\Service\UrlRewriteContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(SameNavigationUrlRewriteHandler::class)]
class SameNavigationUrlRewriteHandlerTest extends TestCase
{
    private SameNavigationUrlRewriteHandler $handler;
    private UrlRewriteContext $urlRewriteContext;
    private PParameterService $pParameterService;
    private UrlRewriterHandlerContext $handlerContext;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->urlRewriteContext = $this->createMock(UrlRewriteContext::class);
        $this->pParameterService = $this->createMock(PParameterService::class);

        $this->handler = new SameNavigationUrlRewriteHandler(
            $this->urlRewriteContext,
            $this->pParameterService,
        );
        $this->handlerContext = $this->createStub(UrlRewriterHandlerContext::class);
    }

    public function testRewriteWithNullResourceLocation(): void
    {
        $url = Url::builder()->build();

        $this->urlRewriteContext->method('getResourceLocation')->willReturn(null);

        $result = $this->handler->rewrite($url, $this->handlerContext);

        $this->assertSame($url, $result);
    }

    public function testRewriteWithNonNullHost(): void
    {
        $url = Url::builder()->host('www.example.com')->build();
        $this->urlRewriteContext->method('getResourceLocation')->willReturn('/path');
        $result = $this->handler->rewrite($url, $this->handlerContext);

        $this->assertSame($url, $result);
    }

    public function testRewriteWithEmptyPath(): void
    {
        $url = Url::builder()->path('')->build();
        $this->urlRewriteContext->method('getResourceLocation')->willReturn('/path');
        $result = $this->handler->rewrite($url, $this->handlerContext);

        $this->assertSame($url, $result);
    }

    public function testRewriteWithValidPath(): void
    {
        $url = Url::builder()->path('/path/bar')->build();

        $this->urlRewriteContext->method('getResourceLocation')->willReturn('/path/foo');
        $this->pParameterService->method('getPParameterForForeignParent')->willReturn('pValue');

        $result = $this->handler->rewrite($url, $this->handlerContext);

        $this->assertEquals('pValue', $result->params['p'] ?? null);
    }
}
