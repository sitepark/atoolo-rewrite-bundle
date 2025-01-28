<?php

declare(strict_types=1);

namespace Atoolo\Rewrite\Dto;

final class UrlRewriteOptions
{
    public function __construct(public readonly bool $toFullyQualifiedUrl) {}

    public static function none(): UrlRewriteOptions
    {
        return (new UrlRewriteOptionsBuilder())->build();
    }
}
