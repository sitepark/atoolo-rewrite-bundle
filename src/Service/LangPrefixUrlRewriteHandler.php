<?php

declare(strict_types=1);

namespace Atoolo\Rewrite\Service;

use Atoolo\Rewrite\Dto\Url;
use Atoolo\Rewrite\Dto\UrlRewriterHandlerContext;
use Atoolo\Rewrite\Dto\UrlRewriteType;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsAlias(id: 'atoolo_rewrite.url_rewrite_handler.lang_prefix')]
class LangPrefixUrlRewriteHandler implements UrlRewriterHandler
{
    private static string $SUPPORTED_LANGUAGE_PATTERN =
        '#^\/(ar|bg|cs|da|de|el|en|es|et|fi|fr|hu|id|it|ja|ko|lt|lv|nb|nl|pl|pt|ro|ru|sk|sl|sv|tr|uk|zh)([\/]?.*)$#';

    private ?string $lang;

    public function __construct(
        RequestStack $requestStack,
        string $prefixForDefaultLang,
    ) {
        $lang = $this->getLangByPathInfo($requestStack);
        $this->lang = $this->considerPrefixForDefaultLang($lang, $prefixForDefaultLang);
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

        if ($this->lang === null) {
            return $url;
        }

        $langPrefix = empty($this->lang) ? '' : '/' . $this->lang;

        return $url->toBuilder()->path($langPrefix . ($url->path ?? '/'))->build();
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

    private function considerPrefixForDefaultLang(
        ?string $lang,
        string $prefixForDefaultLang,
    ): ?string {

        if (empty($prefixForDefaultLang)) {
            return $lang;
        }

        $parts = explode(':', $prefixForDefaultLang);
        if (count($parts) !== 2) {
            return $lang;
        }

        $defaultLang = $parts[0];
        $redirectToLanguagePrefix  = filter_var($parts[1], FILTER_VALIDATE_BOOLEAN);

        if ($lang !== null && $defaultLang === $lang && !$redirectToLanguagePrefix) {
            return '';
        }
        if ($lang === null && $redirectToLanguagePrefix) {
            return $defaultLang;
        }

        return $lang;
    }
}
