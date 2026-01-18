<?php

/**
 * Class Anchor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Traits\TraitDataUrlObject;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Interfaces\AnchorInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Enums\EnumAnchorRenderEmpty;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Html\Traits\TraitUrlRightsRendering;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;


class Anchor extends SpanCore implements AnchorInterface
{
    use TraitUrlRightsRendering;
    use TraitDataUrlObject;


    /**
     * Tracks the url for this anchor
     *
     * @var UrlInterface|null $o_url
     */
    protected ?UrlInterface $o_url = null;


    /**
     * Form class constructor
     *
     * @param RenderInterface|callable|string|float|int|null $content
     * @param RenderInterface|array|callable|string|null     $before_content
     * @param UrlInterface|string|null                       $o_href
     */
    public function __construct(UrlInterface|string|null $o_href = null, RenderInterface|callable|string|float|int|null $content = null, RenderInterface|array|callable|string|null $before_content = null)
    {
        // Execute the ElementCore TraitElementAttributes constructor
        parent::___construct();

        // Setup basic parameters for this object
        $this->setElement('a')
             ->setUrlObject($o_href)
             ->setContent($content)
             ->setBeforeContent($before_content);
    }


    /**
     * Returns a new static class
     *
     * @param UrlInterface|string|null                       $o_href
     * @param RenderInterface|callable|string|float|int|null $content
     * @param RenderInterface|array|callable|string|null     $before_content
     *
     * @return static
     */
    public static function new(UrlInterface|string|null $o_href = null, RenderInterface|callable|string|float|int|null $content = null, RenderInterface|array|callable|string|null $before_content = null): static
    {
        return new static($o_href, $content, $before_content);
    }


    /**
     * Sets the href for this anchor
     *
     * @param UrlInterface|string|null $o_url
     * @param bool                     $reset_rights_cache
     *
     * @return static
     */
    public function setUrlObject(UrlInterface|string|null $o_url, bool $reset_rights_cache = true): static
    {
        $o_url = Url::new($o_url)->makeWww();

        // Run the href through Url to ensure that preconfigured URL's like "sign-out" are converted to full URLs
        $this->o_attributes->set($o_url->getSource(), 'href');

        // Also set the href object itself, and mark that we have to re-update the rights
        $this->o_url = $o_url;

        if ($reset_rights_cache) {
            $this->has_required_rights = false;
        }

        return $this;
    }


    /**
     * Returns the target for this anchor
     *
     * @return EnumAnchorTarget|null
     */
    public function getTargetObject(): ?EnumAnchorTarget
    {
        return $this->o_attributes->get('target', exception: false);
    }


    /**
     * Sets the target for this anchor
     *
     * @param EnumAnchorTarget|null $o_target
     *
     * @return static
     */
    public function setTargetObject(?EnumAnchorTarget $o_target): static
    {
        $this->o_attributes->set($o_target, 'target');
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        // Should we render this URL at all?
        if (!$this->hasRenderRights()) {
            return null;
        }
        if ($this->getUrlObject()->isEmpty()) {
            if (empty($this->content)) {
                // This Anchor contains no URL nor text content to display. Render nothing instead
                return null;
            }

            $this->setElement('span')->addClass('anchor');

        } else {
            if (is_empty($this->content)) {
                switch ($this->render_empty) {
                    case EnumAnchorRenderEmpty::not:
                        return null;

                    case EnumAnchorRenderEmpty::url:
                        // This Anchor contains a URL but no text content to display. Use the URL as content instead
                        $this->setContent($this->o_url->getSource());
                        break;

                    case EnumAnchorRenderEmpty::empty:
                }
            }
        }

        if ($this->child_element) {
            // Render the parent first and use it as content
            if ($this->content) {
                // This A element already has content, cannot have a parent AND content!
                throw new OutOfBoundsException(tr('Cannot render A element, it has child element ":child" and content ":content". It must have one or the other', [
                    ':parent'  => get_class($this->child_element),
                    ':content' => $this->content,
                ]));
            }

            $this->child_element->setAnchorObject(null);
            $this->content = $this->child_element->render();
        }

        return parent::render();
    }
}
