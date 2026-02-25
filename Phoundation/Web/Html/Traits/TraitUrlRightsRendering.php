<?php

/**
 * Trait TraitMode
 *
 * Manages display modes for elements or element blocks
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Enums\EnumAnchorRenderEmpty;
use Phoundation\Web\Html\Enums\EnumAnchorRenderRightsFail;


trait TraitUrlRightsRendering
{
    /**
     * Tracks if the anchor should render anyway even if the user does not have all the required rights
     *
     * @var EnumAnchorRenderRightsFail $render_rights_fail
     */
    protected EnumAnchorRenderRightsFail $render_rights_fail = EnumAnchorRenderRightsFail::not;

    /**
     * Tracks if the anchor should render anyway even if the user does not have all the required rights
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
     * Returns true if the current session user (or the specified one) has access to this URL
     *
     * @param UserInterface|null $_user
     * @param bool               $use_cache
     *
     * @return bool
     */
    public function userHasAccess(?UserInterface $_user = null, bool $use_cache = true): bool
    {
        return $this->getUrlObject()->userHasAccess($_user, $use_cache);
    }


    /**
     * Throws an AccessDeniedException if the current session user (or the specified one) does not have access to this URL
     *
     * @param UserInterface|null $_user
     * @param bool               $use_cache
     *
     * @return static
     */
    public function checkUserAccess(?UserInterface $_user = null, bool $use_cache = true): static
    {
        $this->getUrlObject()->checkUserAccess($_user, $use_cache);
        return $this;
    }


    /**
     * Returns true if the specified user (or if empty, the current Session User) has all the rights required to render this A object
     *
     * @param UserInterface|null $_user
     * @param bool               $cache
     *
     * @return bool
     */
    public function hasRequiredRights(?UserInterface $_user = null, bool $cache = true): bool
    {
        $_user = $_user ?? Session::getUserObject();

        if ($cache and ($this->has_required_rights === $_user)) {
            //  We already know this user has access to the required rights, return cached response
            return true;
        }

        $this->_url->ensureAbsolute();

        $has_required_rights = $_user->getRightsObject()->hasAll($this->getRights());

        if ($has_required_rights) {
            $this->has_required_rights = $_user;
            return true;
        }

        $this->has_required_rights = false;
        return false;
    }


    /**
     * Returns the manually specified required rights to render this Anchor object
     *
     * @param bool $use_cache
     *
     * @return RightsInterface
     */
    public function getRightsObject(bool $use_cache = true): RightsInterface
    {
        return $this->getUrlObject()->getRightsObject($use_cache);
    }


    /**
     * Sets the manually specified required rights to render this Anchor object
     *
     * @param RightsInterface|null $_rights
     *
     * @return static
     */
    protected function setRightsObject(RightsInterface|null $_rights): static
    {
        $this->getUrlObject()->setRightsObject($_rights);
        return $this;
    }


    /**
     * Adds the specified right to the list
     *
     * @param RightInterface|string|null $_right
     *
     * @return static
     */
    public function addRight(RightInterface|string|null $_right): static
    {
        $this->getUrlObject()->addRight($_right);
        return $this;
    }


    /**
     * Removes the specified right from the list
     *
     * @param RightInterface|string|null $_right
     *
     * @return static
     */
    public function removeRight(RightInterface|string|null $_right): static
    {
        $this->getUrlObject()->removeRight($_right);
        return $this;
    }


    /**
     * Sets how this anchor will render if the user does not have all the required rights
     *
     * @param EnumAnchorRenderRightsFail $render_rights_fail
     *
     * @return static
     */
    public function setRenderRightsFail(EnumAnchorRenderRightsFail $render_rights_fail): static
    {
        $this->render_rights_fail = $render_rights_fail;
        return $this;
    }


    /**
     * Returns how this anchor will render if the user does not have all the required rights
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
     * Returns an array of rights that are required to render this Anchor object
     *
     * @param bool $use_cache
     *
     * @return array
     */
    public function getRights(bool $use_cache = true): array
    {
        return $this->getUrlObject()->getRights($use_cache);
    }


    /**
     * Returns true if the current user has the required rights to render this URL object
     */
    protected function hasRenderRights(bool $cache = true): bool
    {
        if ($this->auto_check_rights) {
            if (!$this->hasRequiredRights(cache: $cache)) {
                if (!Core::isProductionEnvironment()) {
                    Log::warning(tr('User ":user" does not have the required rights ":rights" to render the URL ":url"', [
                        ':user'   => Session::getUserObject()->getLogId(),
                        ':rights' => $this->getRights(),
                        ':url'    => $this->getUrlObject()->getSource()
                    ]));
                }

                switch ($this->render_rights_fail) {
                    case EnumAnchorRenderRightsFail::no_url:
                        // Continue rendering the anchor, but without URL by converting it to a <span>
                        $this->setUrlObject(null);

                    // no break
                    case EnumAnchorRenderRightsFail::full:
                        // Continue rendering this anchor as normal.
                        break;

                    case EnumAnchorRenderRightsFail::not:
                        // Do not render the anchor at all
                        return false;

                    case EnumAnchorRenderRightsFail::fail:
                        throw AccessDeniedException::new(tr('Cannot render anchor for URL ":url", the user ":user" does not have the required rights to access this URL', [
                            ':href' => $this->getUrlObject(),
                            ':user' => Session::getUserObject(),
                        ]))
                        ->setData([
                            'required_rights' => $this->getRights(),
                            'href'            => $this->getUrlObject(),
                            'user'            => Session::getUserObject(),
                        ]);
                }
            }
        }

        return true;
    }
}
