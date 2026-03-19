# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Symfony Bundle** (`atoolo/rewrite-bundle`) providing flexible URL rewriting for Symfony applications. It handles language prefix injection, PHP extension stripping, and navigation context-aware URL transformations.

- **Namespace**: `Atoolo\Rewrite\`
- **PHP**: 8.1+ (readonly properties, enums, named arguments)
- **Symfony**: ^6.3 || ^7.1

## Common Commands

```bash
# Install dependencies
composer install

# Run tests with coverage
composer test

# Run a single test file
./vendor/bin/phpunit test/Service/LangPrefixUrlRewriteHandlerTest.php

# Run a single test method
./vendor/bin/phpunit --filter testRewriteLangPrefix test/Service/LangPrefixUrlRewriteHandlerTest.php

# Static analysis (PHPStan Level 9)
composer analyse:phpstan

# Fix code style
composer cs-fix

# Check code style without fixing
composer analyse:phpcsfixer

# Run all analysis tools
composer analyse

# Mutation testing
composer test:infection
```

## Architecture

### Handler Chain Pattern

URL rewriting uses a **Chain of Responsibility** pattern:

1. `UrlRewriteHandlerCollection` implements `UrlRewriter` — the public API
2. It iterates over all registered `UrlRewriterHandler` implementations
3. Each handler receives a `UrlRewriterHandlerContext` (containing the current `Url`, `UrlRewriteOptions`, and `UrlRewriteContext`) and returns a modified `Url`

Handlers are auto-tagged via Symfony DI (`atoolo_rewrite.url_rewrite_handler`) when they implement `UrlRewriterHandler`.

### Key DTOs

- **`Url`**: Immutable value object wrapping a parsed URL (scheme, host, port, path, params, fragment). Central data structure passed through the handler chain.
- **`UrlBuilder`**: Fluent builder for `Url` — use `UrlBuilder::parse($urlString)` to start from an existing URL.
- **`UrlRewriteContext`**: Runtime context (scheme, host, basePath, resourceLocation) — represents the current HTTP request environment.
- **`UrlRewriterHandlerContext`**: Bundles `Url` + `UrlRewriteOptions` + `UrlRewriteContext` for passing through handlers.
- **`UrlRewriteType`**: Enum (`IMAGE`, `MEDIA`, `LINK`) — handlers may apply logic conditionally by type.

### Adding a New Handler

1. Create a class in `src/Service/` implementing `UrlRewriterHandler`
2. Symfony DI auto-registers it and injects it into the chain (via `_instanceof` tagging in `config/services.yaml`)
3. No manual service registration needed

### Symfony DI Configuration

`config/services.yaml` auto-registers everything under `src/Service/` and auto-tags `UrlRewriterHandler` implementations. The parameter `atoolo_rewrite.url_rewrite_handler.lang_prefix.default` controls the default language config (`'de:false'`).

## Testing Conventions

- **Framework**: PHPUnit 10.4+ with `#[CoversClass(...)]` attributes
- **Pattern**: `setUp()` initializes mocks; tests follow Arrange-Act-Assert
- **Mocking**: PHPUnit `createStub()` / `createMock()` (not Mockito — this is PHP)
- **Test execution order**: Randomized (configured in `phpunit.xml`)
- Tests mirror `src/` structure under `test/`

Refer to the global CLAUDE.md for assertion rules (one assertion per test, descriptive messages required).
