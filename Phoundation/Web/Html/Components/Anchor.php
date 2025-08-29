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

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Interfaces\AnchorInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Enums\EnumAnchorRenderEmpty;
use Phoundation\Web\Html\Enums\EnumAnchorRenderRightsFail;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;
use Stringable;


class Anchor extends SpanCore implements AnchorInterface
{
    /**
     * Tracks the url for this anchor
     *
     * @var UrlInterface|null $o_href
     */
    protected ?UrlInterface $o_href = null;

    /**
     * Tracks if the anchor should render anyway even if the user doesn't have all the required rights
     *
     * @var EnumAnchorRenderRightsFail $render_rights_fail
     */
    protected EnumAnchorRenderRightsFail $render_rights_fail = EnumAnchorRenderRightsFail::not;

    /**
     * Tracks if the anchor should render anyway even if the user doesn't have all the required rights
     *
     * @var EnumAnchorRenderEmpty $render_empty
     */
    protected EnumAnchorRenderEmpty $render_empty = EnumAnchorRenderEmpty::not;

    /**
     * Tracks if rights should be checked before rendering
     *
     * @var bool $auto_check_rights
     */
    protected bool $auto_check_rights = true;

    /**
     * Cache that tracks if the current user has the required rights
     *
     * @var UserInterface|false $has_required_rights
     */
    protected UserInterface|false $has_required_rights = false;


    /**
     * Form class constructor
     *
     * @param string|null                                $content
     * @param RenderInterface|array|callable|string|null $before_content
     * @param UrlInterface|string|null                   $o_href
     */
    public function __construct(UrlInterface|string|null $o_href = null, ?string $content = null, RenderInterface|array|callable|string|null $before_content = null)
    {
        // Execute the ElementCore TraitElementAttributes constructor
        parent::___construct();

        // Setup basic parameters for this object
        $this->setElement('a')
             ->setHref($o_href)
             ->setContent($content)
             ->setBeforeContent($before_content);
    }


    /**
     * Returns a new static class
     *
     * @param UrlInterface|string|null                   $o_href
     * @param Stringable|string|null                     $content
     * @param RenderInterface|array|callable|string|null $before_content
     *
     * @return static
     */
    public static function new(UrlInterface|string|null $o_href = null, Stringable|string|null $content = null, RenderInterface|array|callable|string|null $before_content = null): static
    {
        return new static($o_href, $content, $before_content);
    }


    /**
     * Returns the href for this anchor
     *
     * @return UrlInterface|null
     */
    public function getHref(): ?UrlInterface
    {
        return $this->o_href;
    }


    /**
     * Sets the href for this anchor
     *
     * @param UrlInterface|string|null $o_href
     * @param bool                     $reset_rights_cache
     *
     * @return static
     */
    public function setHref(UrlInterface|string|null $o_href, bool $reset_rights_cache = true): static
    {
        $o_href = Url::new($o_href)->makeWww();

        // Run the href through Url to ensure that preconfigured URL's like "sign-out" are converted to full URLs
        $this->o_attributes->set($o_href->getSource(), 'href');

        // Also set the href object itself, and mark that we have to re-update the rights
        $this->o_href = $o_href;

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
    public function getTarget(): ?EnumAnchorTarget
    {
        return $this->o_attributes->get('target', false);
    }


    /**
     * Sets the target for this anchor
     *
     * @param EnumAnchorTarget|null $o_target
     *
     * @return static
     */
    public function setTarget(?EnumAnchorTarget $o_target): static
    {
        $this->o_attributes->set($o_target, 'target');
        return $this;
    }


    /**
     * Returns an array of rights that are required to render this Anchor object
     *
     * @return array
     */
    public function getRequiredRights(): array
    {
        return $this->getHref()->getRequiredRights();
    }


    /**
     * Returns true if the current session user (or the specified one) has access to this URL
     *
     * @param UserInterface|null $o_user
     *
     * @return bool
     */
    public function userHasAccess(?UserInterface $o_user = null): bool
    {
        return $this->getHref()->userHasAccess($o_user);
    }


    /**
     * Throws an AccessDeniedException if the current session user (or the specified one) doesn't have access to this URL
     *
     * @param UserInterface|null $o_user
     *
     * @return static
     * @throws AccessDeniedException
     */
    public function checkUserAccess(?UserInterface $o_user = null): static
    {
        $this->getHref()->checkUserAccess($o_user);
        return $this;
    }


    /**
     * Returns true if the specified user (or if empty, the current Session User) has all the rights required to render this A object
     *
     * @param UserInterface|null $o_user
     * @param bool               $force
     *
     * @return bool
     */
    public function hasRequiredRights(?UserInterface $o_user = null, bool $force = false): bool
    {
        $o_user = $o_user ?? Session::getUserObject();

        if (!$force and $this->has_required_rights and ($this->has_required_rights === $o_user)) {
            //  We already know this user has access to the required rights, return cached response
            return true;
        }

        $has_required_rights = $o_user->getRightsObject()->hasAll($this->getRequiredRights());

        if ($has_required_rights) {
            $this->has_required_rights = $o_user;
            return true;
        }

        $this->has_required_rights = false;
        return false;
    }


    /**
     * Returns the manually specified required rights to render this Anchor object
     *
     * @param bool $reload
     * @param bool $order
     *
     * @return RightsInterface
     */
    public function getRightsObject(bool $reload = false, bool $order = false): RightsInterface
    {
        return $this->getHref()->getRightsObject($reload, $order);
    }


    /**
     * Sets the manually specified required rights to render this Anchor object
     *
     * @param RightsInterface|null $o_rights
     *
     * @return static
     */
    protected function setRightsObject(RightsInterface|null $o_rights): static
    {
        $this->getHref()->setRightsObject($o_rights);
        return $this;
    }


    /**
     * Adds the specified right to the list
     *
     * @param RightInterface|string|null $o_right
     *
     * @return $this
     */
    public function addRight(RightInterface|string|null $o_right): static
    {
        $this->getHref()->addRight($o_right);
        return $this;
    }


    /**
     * Removes the specified right from the list
     *
     * @param RightInterface|string|null $o_right
     *
     * @return $this
     */
    public function removeRight(RightInterface|string|null $o_right): static
    {
        $this->getHref()->removeRight($o_right);
        return $this;
    }


    /**
     * Sets how this anchor will render if the user doesn't have all the required rights
     *
     * @param EnumAnchorRenderRightsFail $render_rights_fail
     *
     * @return $this
     */
    public function setRenderRightsFail(EnumAnchorRenderRightsFail $render_rights_fail): static
    {
        $this->render_rights_fail = $render_rights_fail;
        return $this;
    }


    /**
     * Returns how this anchor will render if the user doesn't have all the required rights
     *
     * @return EnumAnchorRenderRightsFail
     */
    public function getRenderRightsFail(): EnumAnchorRenderRightsFail
    {
        return $this->render_rights_fail;
    }


    /**
     * Returns if the rights should be checked automatically on rendering
     *
     * @return bool
     */
    public function getAutoCheckRights(): bool
    {
        return $this->auto_check_rights;
    }


    /**
     * Sets if the rights should be checked automatically on rendering
     *
     * @param bool $check_rights
     *
     * @return Anchor
     */
    public function setAutoCheckRights(bool $check_rights): static
    {
        $this->auto_check_rights = $check_rights;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        if ($this->auto_check_rights) {
            if (!$this->hasRequiredRights()) {
                if (!Core::isProductionEnvironment()) {
                    Log::warning(tr('User ":user" does not have the required rights ":rights" to render the URL ":url"', [
                        ':user'   => Session::getUserObject()->getLogId(),
                        ':rights' => $this->getRequiredRights(),
                        ':url'    => $this->getHref()->getSource()
                    ]));
                }

                switch ($this->render_rights_fail) {
                    case EnumAnchorRenderRightsFail::no_url:
                        // Continue rendering the anchor, but without URL by converting it to a <span>
                        $this->setHref(null);
                    // no break

                    case EnumAnchorRenderRightsFail::full:
                        // Continue rendering this anchor as normal.
                        break;

                    case EnumAnchorRenderRightsFail::not:
                        // Don't render the anchor at all
                        return null;

                    case EnumAnchorRenderRightsFail::fail:
                        throw AccessDeniedException::new(tr('Cannot render anchor for URL ":url", the user ":user" does not have the required rights to access this URL', [
                            ':href' => $this->getHref(),
                            ':user' => Session::getUserObject(),
                        ]))->setData([
                            'required_rights' => $this->getRequiredRights(),
                            'href'            => $this->getHref(),
                            'user'            => Session::getUserObject(),
                        ]);
                }
            }
        }

        if ($this->getHref()->isEmpty()) {
            if (empty($this->content)) {
                // This Anchor contains no URL nor text content to display. Render nothing instead
                return null;
            }

            $this->setElement('span')->addClass('anchor');

        } else {
            if (empty($this->content)) {
                switch ($this->render_empty) {
                    case EnumAnchorRenderEmpty::not:
                        return null;

                    case EnumAnchorRenderEmpty::url:
                        // This Anchor contains a URL but no text content to display. Use the URL as content instead
                        $this->setContent($this->o_href->getSource());
                        break;

                    case EnumAnchorRenderEmpty::empty:
                }
            }
        }

        if ($this->child_element) {
            // Render the parent first and use it as content
            if ($this->content) {
                // This A element already has content, can't have a parent AND content!
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
