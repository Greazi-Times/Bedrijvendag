<?php

namespace App\Support;

use Dom\Element;
use Dom\HTMLDocument;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The one formatting policy for company and partner descriptions, used when
 * they are saved and again when they are rendered (so older content follows it
 * too). Allowlist: paragraphs, line breaks, bold, italic, lists, http(s)/mailto
 * links and h3/h4 headings. Other wrappers (span, font, div, Word's o:p, tables)
 * are unwrapped so their text survives; embeds and scripts are dropped whole.
 */
class RichText
{
    private const DROPPED = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'img', 'picture', 'source',
        'video', 'audio', 'track', 'canvas', 'svg', 'math', 'template', 'noscript', 'form', 'input', 'button',
        'select', 'option', 'textarea', 'head', 'title', 'meta', 'link', 'base',
    ];

    /** Layout containers from pasted pages; a leaf one becomes a paragraph so its text keeps its own line. */
    private const BLOCK_SELECTOR = 'div, section, article, header, footer, main, aside, nav, blockquote, pre, address, figure, figcaption, center, td, th, caption, dt, dd';

    private const BLOCK_CHILDREN = 'p, ul, ol, h1, h2, h3, h4, h5, h6, table, '.self::BLOCK_SELECTOR;

    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = self::sanitizer()->sanitize(self::paragraphsFromBlocks($html));

        // The sanitizer has already stripped every attribute from these tags,
        // so exact tag names are safe to rewrite here.
        $clean = preg_replace(['#<(/?)h[12]>#', '#<(/?)h[56]>#'], ['<$1h3>', '<$1h4>'], $clean) ?? $clean;

        // Word and web pastes leave empty paragraphs and runs of line breaks behind.
        $clean = preg_replace('#<p>(?:\s|&nbsp;|&\#160;|\x{00A0}|<br />)*</p>#u', '', $clean) ?? $clean;
        $clean = preg_replace('#(?:<br />\s*){3,}#', '<br /><br />', $clean) ?? $clean;

        $clean = trim($clean);

        return trim(strip_tags($clean)) === '' ? null : $clean;
    }

    private static function paragraphsFromBlocks(string $html): string
    {
        $document = HTMLDocument::createFromString('<!DOCTYPE html><body>'.$html.'</body>', LIBXML_NOERROR, 'UTF-8');
        $body = $document->body;

        if (! $body) {
            return $html;
        }

        // Deepest first, so a container is judged after its children are already paragraphs.
        $blocks = array_reverse(iterator_to_array($body->querySelectorAll(self::BLOCK_SELECTOR)));

        foreach ($blocks as $block) {
            /** @var Element $block */
            if ($block->querySelector(self::BLOCK_CHILDREN) !== null) {
                continue;
            }

            $paragraph = $document->createElement('p');

            while ($block->firstChild) {
                $paragraph->appendChild($block->firstChild);
            }

            $block->replaceWith($paragraph);
        }

        return $body->innerHTML;
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer) {
            return self::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(200000);

        foreach (['p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $element) {
            $config = $config->allowElement($element);
        }

        $config = $config->allowElement('a', ['href']);

        foreach (self::DROPPED as $element) {
            $config = $config->dropElement($element);
        }

        return self::$sanitizer = new HtmlSanitizer($config);
    }
}
