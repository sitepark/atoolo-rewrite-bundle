<?php

declare(strict_types=1);

namespace Atoolo\Rewrite\Dto;

final class UrlRewriteOptionsBuilder
{
    private bool $toFullyQualifiedUrl = false;

    public function __construct() {}

    public function toFullyQualifiedUrl(bool $toFullyQualifiedUrl): self
    {
        $this->toFullyQualifiedUrl = $toFullyQualifiedUrl;
        return $this;
    }

    public function build(): UrlRewriteOptions
    {
        return new UrlRewriteOptions($this->toFullyQualifiedUrl);
    }
}
