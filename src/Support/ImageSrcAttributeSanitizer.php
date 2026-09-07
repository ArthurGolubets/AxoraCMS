<?php

namespace HolartWeb\AxoraCMS\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Restricts <img src> data: URIs to images only.
 *
 * symfony/html-sanitizer allows a scheme wholesale, so `data:` would also let
 * `data:text/html,...` through. This narrows it back down to `data:image/*`
 * while leaving http/https/relative URLs untouched.
 */
class ImageSrcAttributeSanitizer implements AttributeSanitizerInterface
{
    /**
     * @return list<string>|null
     */
    public function getSupportedElements(): ?array
    {
        return ['img'];
    }

    /**
     * @return list<string>|null
     */
    public function getSupportedAttributes(): ?array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $normalized = strtolower(preg_replace('/[\s\x00-\x1F]+/', '', $value) ?? '');

        if (str_starts_with($normalized, 'data:') && ! str_starts_with($normalized, 'data:image/')) {
            return null;
        }

        return $value;
    }
}
