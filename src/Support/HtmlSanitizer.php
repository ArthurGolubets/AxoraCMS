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

        return trim(self::sanitizer()->sanitize($html));
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
