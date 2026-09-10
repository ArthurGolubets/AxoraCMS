<?php

namespace HolartWeb\AxoraCMS\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Wrapper around symfony/html-sanitizer for admin-managed rich text
 * (product / catalog descriptions, CommerceML import content).
 *
 * The configuration below is intentionally strict: only formatting markup
 * used in product descriptions is allowed, every event handler / script /
 * style / iframe / form is dropped and URL attributes are scheme-checked.
 */
class HtmlSanitizer
{
    /**
     * Elements allowed together with their permitted attributes.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_ELEMENTS = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'target', 'rel', 'title'],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'blockquote' => [],
        'pre' => [],
        'code' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
        'img' => ['src', 'alt', 'width', 'height'],
        'span' => [],
        'div' => [],
    ];

    private static ?SymfonyHtmlSanitizer $sanitizer = null;

    /**
     * Clean an untrusted HTML fragment. Returns an empty string for null/empty input.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // symfony/html-sanitizer is the preferred backend, but never let a
        // missing dependency (or a stale opcache after `composer require`) turn
        // a routine content save into a 500 — fall back to a DOM allow-list.
        if (! class_exists(HtmlSanitizerConfig::class)) {
            return self::cleanWithDom($html);
        }

        try {
            return trim(self::sanitizer()->sanitize($html));
        } catch (\Throwable $e) {
            return self::cleanWithDom($html);
        }
    }

    /**
     * Dependency-free fallback: keep only allow-listed elements/attributes,
     * unwrap everything else and drop dangerous tags entirely.
     */
    private static function cleanWithDom(string $html): string
    {
        $dangerous = ['script', 'style', 'iframe', 'form', 'object', 'embed', 'link', 'meta', 'base'];

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="axora-sanitizer-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('axora-sanitizer-root');
        if (! $root) {
            return trim(strip_tags($html));
        }

        $xpath = new \DOMXPath($document);

        // Remove dangerous elements outright.
        foreach ($xpath->query('.//'.implode(' | .//', $dangerous), $root) as $node) {
            $node->parentNode?->removeChild($node);
        }

        // Walk a static snapshot bottom-up so unwrapping doesn't break iteration.
        $elements = iterator_to_array($xpath->query('.//*', $root));
        foreach (array_reverse($elements) as $element) {
            /** @var \DOMElement $element */
            $tag = strtolower($element->nodeName);

            if (! array_key_exists($tag, self::ALLOWED_ELEMENTS)) {
                // Unwrap: replace the element with its children.
                while ($element->firstChild) {
                    $element->parentNode?->insertBefore($element->firstChild, $element);
                }
                $element->parentNode?->removeChild($element);

                continue;
            }

            $allowedAttributes = self::ALLOWED_ELEMENTS[$tag];
            foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
                $name = strtolower($attribute->nodeName);
                $keep = in_array($name, $allowedAttributes, true)
                    && ! self::isUnsafeUrlAttribute($name, $attribute->nodeValue);

                if (! $keep) {
                    $element->removeAttribute($attribute->nodeName);
                }
            }

            if ($tag === 'a') {
                $element->setAttribute('rel', 'noopener nofollow');
            }
        }

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function isUnsafeUrlAttribute(string $name, ?string $value): bool
    {
        if (! in_array($name, ['href', 'src'], true) || $value === null) {
            return false;
        }

        $value = strtolower(trim($value));

        if ($value === '' || str_starts_with($value, '#') || str_starts_with($value, '/')) {
            return false;
        }

        if (str_starts_with($value, 'data:image/')) {
            return false;
        }

        return ! preg_match('#^(https?:|mailto:)#', $value);
    }

    private static function sanitizer(): SymfonyHtmlSanitizer
    {
        if (self::$sanitizer instanceof SymfonyHtmlSanitizer) {
            return self::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            // Only http/https/mailto links; every media stays http/https or a
            // data:image/* URI (enforced by the attribute sanitizer below).
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowMediaSchemes(['http', 'https', 'data'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withAttributeSanitizer(new ImageSrcAttributeSanitizer)
            // Outbound links must not leak the referrer or pass link juice.
            ->forceAttribute('a', 'rel', 'noopener nofollow');

        foreach (self::ALLOWED_ELEMENTS as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        // Belt and braces: explicitly drop the dangerous elements even though
        // they are not in the allow-list above.
        foreach (['script', 'style', 'iframe', 'form', 'object', 'embed'] as $element) {
            $config = $config->dropElement($element);
        }

        return self::$sanitizer = new SymfonyHtmlSanitizer($config);
    }
}
