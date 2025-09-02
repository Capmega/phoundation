<?php

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Enums\EnumAnchorRenderRightsFail;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Http\Interfaces\UrlInterface;

interface AnchorInterface extends SpanInterface
{
    /**
     * Returns the href for this anchor
     *
     * @return UrlInterface|null
     */
    public function getHref(): ?UrlInterface;


    /**
     * Sets the href for this anchor
     *
     * @param UrlInterface|string|null $o_href
     * @param bool                     $reset_rights_cache
     *
     * @return static
     */
    public function setHref(UrlInterface|string|null $o_href, bool $reset_rights_cache = true): static;


    /**
     * Returns the target for this anchor
     *
     * @return EnumAnchorTarget|null
     */
    public function getTarget(): ?EnumAnchorTarget;


    /**
     * Sets the target for this anchor
     *
     * @param EnumAnchorTarget|null $o_target
     *
     * @return static
     */
    public function setTarget(?EnumAnchorTarget $o_target): static;


    /**
     * Returns an array of rights that are required to render this Anchor object
     *
     * @return array
     */
    public function getRequiredRights(): array;


    /**
     * Returns true if the current session user (or the specified one) has access to this URL
     *
     * @param UserInterface|null $o_user
     *
     * @return bool
     */
    public function userHasAccess(?UserInterface $o_user = null): bool;


    /**
     * Throws an AccessDeniedException if the current session user (or the specified one) doesn't have access to this URL
     *
     * @param UserInterface|null $o_user
     *
     * @return static
     * @throws AccessDeniedException
     */
    public function checkUserAccess(?UserInterface $o_user = null): static;


    /**
     * Returns true if the specified user (or if empty, the current Session User) has all the rights required to render this A object
     *
     * @param UserInterface|null $o_user
     * @param bool               $force
     *
     * @return bool
     */
    public function hasRequiredRights(?UserInterface $o_user = null, bool $force = false): bool;


    /**
     * Returns the manually specified required rights to render this Anchor object
     *
     * @param bool $reload
     * @param bool $order
     *
     * @return RightsInterface
     */
    public function getRightsObject(bool $reload = false, bool $order = false): RightsInterface;


    /**
     * Adds the specified right to the list
     *
     * @param RightInterface|string|null $o_right
     *
     * @return $this
     */
    public function addRight(RightInterface|string|null $o_right): static;


    /**
     * Removes the specified right from the list
     *
     * @param RightInterface|string|null $o_right
     *
     * @return $this
     */
    public function removeRight(RightInterface|string|null $o_right): static;


    /**
     * Sets how this anchor will render if the user doesn't have all the required rights
     *
     * @param EnumAnchorRenderRightsFail $render_rights_fail
     *
     * @return $this
     */
    public function setRenderRightsFail(EnumAnchorRenderRightsFail $render_rights_fail): static;


    /**
     * Returns how this anchor will render if the user doesn't have all the required rights
     *
     * @return EnumAnchorRenderRightsFail
     */
    public function getRenderRightsFail(): EnumAnchorRenderRightsFail;


    /**
     * Returns if the rights should be checked automatically on rendering
     *
     * @return bool
     */
    public function getAutoCheckRights(): bool;


    /**
     * Sets if the rights should be checked automatically on rendering
     *
     * @param bool $check_rights
     *
     * @return Anchor
     */
    public function setAutoCheckRights(bool $check_rights): static;
}
