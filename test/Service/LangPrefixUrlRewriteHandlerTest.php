<?php

declare(strict_types=1);

namespace Atoolo\Rewrite\Test\Service;

use Atoolo\Resource\LangPath;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\Service\LangPathService;
use Atoolo\Rewrite\Dto\Url;
use Atoolo\Rewrite\Dto\UrlRewriteOptions;
use Atoolo\Rewrite\Dto\UrlRewriterHandlerContext;
use Atoolo\Rewrite\Dto\UrlRewriteType;
use Atoolo\Rewrite\Service\LangPrefixUrlRewriteHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(LangPrefixUrlRewriteHandler::class)]
class LangPrefixUrlRewriteHandlerTest extends TestCase
{
    private RequestStack $requestStack;
    private Request $request;
    private ResourceChannel $resourceChannel;
    private LangPathService $langPathService;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->request = $this->createStub(Request::class);
        $this->requestStack = $this->createStub(RequestStack::class);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
        $this->resourceChannel = ResourceChannel::create([
            'translationLocales' => ['en_US', 'it_IT'],
        ]);
        $this->langPathService = $this->createMock(LangPathService::class);
    }

    public function testRewriteLangPrefix(): void
    {

        $this->request->method('getPathInfo')->willReturn('/en/foo/bar.php');
        $this->langPathService->method('parse')->willReturn(new LangPath('en', 'en_US', '/foo/bar.php'));

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:true',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/en/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    public function testRewriteLangPrefixWithoutTranslationLocales(): void
    {

        $resourceChannel = ResourceChannel::create([]);

        $this->request->method('getPathInfo')->willReturn('/en/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:true',
            $resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    public function testRewriteWithPrefixForDefaultLang(): void
    {

        $this->langPathService->method('parse')->willReturn(new LangPath(null, null, '/foo/bar.php'));

        $this->request->method('getPathInfo')->willReturn('/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:true',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/de/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    public function testRewriteWithoutPrefixForDefaultLang(): void
    {

        $this->langPathService->method('parse')->willReturn(new LangPath(null, null, '/foo/bar.php'));

        $this->request->method('getPathInfo')->willReturn('/de/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:false',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    public function testRewriteWithFullQualifiedUrl(): void
    {

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:true',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->parse('https://www.example.com/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->parse('https://www.example.com/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    public function testRewriteWithNonLink(): void
    {

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:true',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.pdf')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::MEDIA),
        );

        $expected = Url::builder()->path('/foo/bar.pdf')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    /**
     * @throws Exception
     */
    public function testRewriteWithNullRequest(): void
    {

        $this->langPathService->method('parse')->willReturn(new LangPath(null, null, '/foo/bar.php'));

        $handler = new LangPrefixUrlRewriteHandler(
            $this->createStub(RequestStack::class),
            'de:false',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    /**
     * @throws Exception
     */
    public function testRewriteWithEmptyPrefixForDefaultLang(): void
    {

        $this->langPathService->method('parse')->willReturn(new LangPath(null, null, '/foo/bar.php'));

        $handler = new LangPrefixUrlRewriteHandler(
            $this->createStub(RequestStack::class),
            '',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    /**
     * @throws Exception
     */
    public function testRewriteWithInvalidPrefixForDefaultLang(): void
    {

        $this->langPathService->method('parse')->willReturn(new LangPath(null, null, '/foo/bar.php'));

        $handler = new LangPrefixUrlRewriteHandler(
            $this->createStub(RequestStack::class),
            'invalid-format',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }


    public function testGetLangFromPathReturnsNullWhenPatternIsNull(): void
    {
        $resourceChannel = ResourceChannel::create([]);

        $handler = new LangPrefixUrlRewriteHandler(
            $this->createStub(RequestStack::class),
            'de:false',
            $resourceChannel,
            $this->langPathService,
        );

        $method = new \ReflectionMethod($handler, 'getLangFromPath');
        $result = $method->invoke($handler, '/foo/bar');

        $this->assertNull(
            $result,
            'getLangFromPath should return null when supportedLanguagePattern is null.',
        );
    }

    public function testRewriteStripsNonDefaultLangPrefixForDefaultLangContext(): void
    {
        $this->langPathService->method('parse')->willReturn(new LangPath('en', 'en_US', '/foo/bar.php'));
        $this->request->method('getPathInfo')->willReturn('/de/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:false',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/en/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The non-default language prefix should be stripped when the current context uses the default language without a prefix.',
        );
    }

    public function testRewriteRetainsUrlWhenLangPrefixAlreadyMatches(): void
    {
        $this->langPathService->method('parse')->willReturn(new LangPath('/en', 'en_US', '/foo/bar.php'));
        $this->request->method('getPathInfo')->willReturn('/en/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:false',
            $this->resourceChannel,
            $this->langPathService,
        );

        $origin = Url::builder()->path('/en/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = Url::builder()->path('/en/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should remain unchanged when the resolved language prefix already matches the parsed lang.',
        );
    }

    private function createContext(Url $origin, UrlRewriteType $type): UrlRewriterHandlerContext
    {
        return new UrlRewriterHandlerContext(
            origin: $origin,
            type: $type,
            options: UrlRewriteOptions::none(),
        );
    }
}
