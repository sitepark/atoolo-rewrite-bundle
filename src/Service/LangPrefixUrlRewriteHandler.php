<?php

declare(strict_types=1);

namespace Atoolo\Rewrite\Service;

use Atoolo\Rewrite\Dto\Url;
use Atoolo\Rewrite\Dto\UrlRewriterHandlerContext;
use Atoolo\Rewrite\Dto\UrlRewriteType;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsAlias(id: 'atoolo_rewrite.url_rewrite_handler.lang_prefix')]
class LangPrefixUrlRewriteHandler implements UrlRewriterHandler
{
    private static string $SUPPORTED_LANGUAGE_PATTERN =
        '#^\/(ar|bg|cs|da|de|el|en|es|et|fi|fr|hu|id|it|ja|ko|lt|lv|nb|nl|pl|pt|ro|ru|sk|sl|sv|tr|uk|zh)([\/]?.*)$#';

    private ?string $defaultLang = null;

    private bool $redirectToDefaultLangPrefix;

    private ?string $pathInfoLang;

    public function __construct(
        RequestStack $requestStack,
        #[Autowire(param: 'atoolo_rewrite.url_rewrite_handler.lang_prefix.default')]
        string $prefixForDefaultLang,
    ) {
        $this->pathInfoLang = $this->getLangByPathInfo($requestStack);

        if (!empty($prefixForDefaultLang)) {
            $parts = explode(':', $prefixForDefaultLang);
            if (count($parts) === 2) {
                $this->defaultLang = $parts[0];
                $this->redirectToDefaultLangPrefix = filter_var($parts[1], FILTER_VALIDATE_BOOLEAN);
            }
        }
    }

    public function rewrite(
        Url $url,
        UrlRewriterHandlerContext $context,
    ): Url {
        // rewrite only internal urls
        if ($context->origin->isFullyQualified()) {
            return $url;
        }

        // rewrite only internal resources
        if ($context->type !== UrlRewriteType::LINK) {
            return $url;
        }

        $langPathPrefix = $this->getLangPathPrefix($context->options->lang ?? $this->pathInfoLang);

        if ($langPathPrefix === '') {
            return $url;
        }

        return $url->toBuilder()->path($langPathPrefix . ($url->path ?? '/'))->build();
    }

    private function getLangByPathInfo(RequestStack $requestStack): ?string
    {
        $request = $requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        if (preg_match(
            self::$SUPPORTED_LANGUAGE_PATTERN,
            $request->getPathInfo(),
            $matches,
        ) === 0) {
            return null;
        }

        return $matches[1];
    }

    private function getLangPathPrefix(?string $lang): string
    {

        $langPath = $lang;

        if ($lang !== null && $this->defaultLang === $lang && !$this->redirectToDefaultLangPrefix) {
            $langPath = '';
        }
        if ($lang === null && $this->defaultLang !== null && $this->redirectToDefaultLangPrefix) {
            $langPath = $this->defaultLang;
        }

        if (empty($langPath)) {
            return '';
        }

        return '/' . $langPath;
    }
}
