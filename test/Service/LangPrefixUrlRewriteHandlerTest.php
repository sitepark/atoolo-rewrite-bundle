<?php

declare(strict_types=1);

namespace Atoolo\Rewrite\Test\Service;

use Atoolo\Rewrite\Dto\Url;
use Atoolo\Rewrite\Dto\UrlBuilder;
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

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->request = $this->createStub(Request::class);
        $this->requestStack = $this->createStub(RequestStack::class);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
    }

    public function testRewriteLangPrefix(): void
    {

        $this->request->method('getPathInfo')->willReturn('/en/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:true',
        );

        $origin = (new UrlBuilder())->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = (new UrlBuilder())->path('/en/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    public function testRewriteWithPrefixForDefaultLang(): void
    {

        $this->request->method('getPathInfo')->willReturn('/de/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:true',
        );

        $origin = (new UrlBuilder())->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = (new UrlBuilder())->path('/de/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
        );
    }

    public function testRewriteWithoutPrefixForDefaultLang(): void
    {

        $this->request->method('getPathInfo')->willReturn('/de/foo/bar.php');

        $handler = new LangPrefixUrlRewriteHandler(
            $this->requestStack,
            'de:false',
        );

        $origin = (new UrlBuilder())->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = (new UrlBuilder())->path('/foo/bar.php')->build();

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
        );

        $origin = (new UrlBuilder())->parse('https://www.example.com/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = (new UrlBuilder())->parse('https://www.example.com/foo/bar.php')->build();

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
        );

        $origin = (new UrlBuilder())->path('/foo/bar.pdf')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::MEDIA),
        );

        $expected = (new UrlBuilder())->path('/foo/bar.pdf')->build();

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

        $handler = new LangPrefixUrlRewriteHandler(
            $this->createStub(RequestStack::class),
            'de:false',
        );

        $origin = (new UrlBuilder())->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = (new UrlBuilder())->path('/foo/bar.php')->build();

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

        $handler = new LangPrefixUrlRewriteHandler(
            $this->createStub(RequestStack::class),
            '',
        );

        $origin = (new UrlBuilder())->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = (new UrlBuilder())->path('/foo/bar.php')->build();

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

        $handler = new LangPrefixUrlRewriteHandler(
            $this->createStub(RequestStack::class),
            'invalid-format',
        );

        $origin = (new UrlBuilder())->path('/foo/bar.php')->build();
        $url = $handler->rewrite(
            $origin,
            $this->createContext($origin, UrlRewriteType::LINK),
        );

        $expected = (new UrlBuilder())->path('/foo/bar.php')->build();

        $this->assertEquals(
            $expected,
            $url,
            'The URL should be rewritten by the handler.',
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
