<?php

/**
 * @package     plg_system_fgremovegenerator
 * @copyright   (C) 2026 Fero
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace FG\Plugin\System\Fgremovegenerator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Document\FeedDocument;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Application\AfterRenderEvent;
use Joomla\CMS\Event\Application\BeforeCompileHeadEvent;
use Joomla\CMS\Event\Application\BeforeRespondEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;

/**
 * Removes the Joomla generator meta tag (also from RSS/Atom feeds), the optional
 * author meta tag and optional fingerprinting HTTP headers (X-Powered-By, X-Generator).
 *
 * Requires Joomla 5.0+ (uses the concrete Application event classes introduced
 * in Joomla 5.0.0; these do not exist in Joomla 4).
 */
final class Fgremovegenerator extends CMSPlugin implements SubscriberInterface
{
    /**
     * All handlers run with the lowest priority, i.e. after every other plugin on the
     * same event, so nothing registered later can set the generator or a header again.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => ['onBeforeCompileHead', Priority::MIN],
            'onAfterRender'       => ['onAfterRender', Priority::MIN],
            'onBeforeRespond'     => ['onBeforeRespond', Priority::MIN],
        ];
    }

    /**
     * Removes selected fingerprinting HTTP headers as late as possible in the
     * application lifecycle — right before Joomla sends the HTTP response.
     *
     * Two places have to be cleaned: headers already registered with PHP (X-Powered-By
     * from expose_php, raw header() calls) and headers kept in Joomla's own response
     * object (set with $app->setHeader(), e.g. X-Powered-By: JoomlaAPI/1.0 in the API
     * application). Joomla sends the latter in respond(), i.e. after this event, so
     * header_remove() alone does not see them.
     */
    public function onBeforeRespond(BeforeRespondEvent $event): void
    {
        $headersToRemove = [
            'remove_x_powered_by' => 'X-Powered-By',
            'remove_x_generator'  => 'X-Generator',
        ];

        $names = [];

        foreach ($headersToRemove as $param => $header) {
            if ((int) $this->params->get($param, 0)) {
                $names[] = $header;
            }
        }

        if ($names === [] || headers_sent()) {
            return;
        }

        foreach ($names as $name) {
            header_remove($name);
        }

        $app = $event->getApplication();

        if (!method_exists($app, 'getResponse') || !method_exists($app, 'setResponse')) {
            return;
        }

        $response = $app->getResponse();
        $changed  = false;

        foreach ($names as $name) {
            if ($response->hasHeader($name)) {
                $response = $response->withoutHeader($name);
                $changed  = true;
            }
        }

        if ($changed) {
            $app->setResponse($response);
        }
    }

    public function onBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $app = $event->getApplication();

        if (!$this->appliesTo($app)) {
            return;
        }

        $document = $event->getDocument();

        if (!$document instanceof HtmlDocument) {
            return;
        }

        $mode = (string) $this->params->get('mode', 'remove');

        if ($mode === 'custom') {
            $document->setGenerator((string) $this->params->get('custom_text', ''));
        } else {
            // Empty generator string = Joomla renders no generator meta tag at all
            $document->setGenerator('');
        }

        if ((int) $this->params->get('remove_author', 0)) {
            $this->removeAuthorMeta($document);
        }
    }

    /**
     * Joomla writes the generator into RSS/Atom feeds as an element (Atom also adds a
     * joomla.org link and, optionally, the version) and as an XML comment at the top.
     * onBeforeCompileHead is never fired for feeds, so the rendered output is cleaned.
     */
    public function onAfterRender(AfterRenderEvent $event): void
    {
        if (!(int) $this->params->get('clean_feeds', 1)) {
            return;
        }

        $app = $event->getApplication();

        if (!$this->appliesTo($app)
            || !method_exists($app, 'getDocument')
            || !method_exists($app, 'getBody')
            || !method_exists($app, 'setBody')
            || !$app->getDocument() instanceof FeedDocument
        ) {
            return;
        }

        $body = (string) $app->getBody();

        if ($body === '') {
            return;
        }

        $custom = (string) $this->params->get('mode', 'remove') === 'custom'
            ? trim((string) $this->params->get('custom_text', ''))
            : '';

        $clean = self::cleanFeed($body, $custom);

        if ($clean !== $body) {
            $app->setBody($clean);
        }
    }

    /**
     * Removes (or, with a non-empty $custom text, replaces) the first generator element
     * and the first generator comment of a feed. Only the first match is touched, so
     * feed items are never rewritten.
     */
    public static function cleanFeed(string $body, string $custom = ''): string
    {
        $escaped = htmlspecialchars($custom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($custom === '') {
            $body = (string) preg_replace('#[ \t]*<generator\b[^>]*>.*?</generator>[ \t]*(?:\r\n|\n)?#is', '', $body, 1);
            $body = (string) preg_replace('#<!--\s*generator="[^"]*"\s*-->[ \t]*(?:\r\n|\n)?#i', '', $body, 1);

            return $body;
        }

        $element = '<generator>' . $escaped . '</generator>';
        $comment = '<!-- generator="' . str_replace('--', '-', $escaped) . '" -->';

        $body = (string) preg_replace_callback('#<generator\b[^>]*>.*?</generator>#is', static fn (): string => $element, $body, 1);
        $body = (string) preg_replace_callback('#<!--\s*generator="[^"]*"\s*-->#i', static fn (): string => $comment, $body, 1);

        return $body;
    }

    /** Always on the frontend; on the backend only if explicitly enabled. */
    private function appliesTo(mixed $app): bool
    {
        if (!$app instanceof CMSApplicationInterface) {
            return false;
        }

        if ($app->isClient('administrator')) {
            return (bool) (int) $this->params->get('apply_admin', 0);
        }

        return $app->isClient('site');
    }

    /** Joomla adds <meta name="author"> to articles when "Show Author Meta Tag" is on. */
    private function removeAuthorMeta(HtmlDocument $document): void
    {
        $head = $document->getHeadData();

        if (empty($head['metaTags']['name']) || !is_array($head['metaTags']['name'])) {
            return;
        }

        $changed = false;

        foreach (array_keys($head['metaTags']['name']) as $key) {
            if (strtolower((string) $key) === 'author') {
                unset($head['metaTags']['name'][$key]);
                $changed = true;
            }
        }

        if ($changed) {
            // setHeadData() only overwrites the keys that are passed in.
            $document->setHeadData(['metaTags' => $head['metaTags']]);
        }
    }
}
