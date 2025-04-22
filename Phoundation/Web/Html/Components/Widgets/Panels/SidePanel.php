<?php

/**
 * SidePanel class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels;

use Phoundation\Web\Html\Components\Widgets\Modals\SignInModal;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;


class SidePanel extends Panel
{
    /**
     * SidePanel class constructor
     *
     * @param string|null $source
     */
    public function __construct(?string $source = null)
    {
        parent::__construct($source);

// TODO Re-add support for signinmodal for AJAX sign-in
//        $this->getModals()
//             ->addModal('sign-in', new SignInModal());
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->setMenu(Request::getMenusObject()
                              ->getPrimaryMenu());

// TODO Re-enable support for sign-in modal, and other modals
//        $sign_in = new SignInModal();
//        $sign_in->useForm(true)
//                ->getForm()
//                ->setId('form-sign-in')
//                ->setRequestMethod(EnumHttpRequestMethod::post)
//                ->setAction(Url::new('sign-in')->makeAjax());
//        $this->setMenu(Request::getMenusObject()
//                              ->getPrimaryMenu())
//             ->getModals()
//             ->addModal('sign-in', $sign_in);

        return parent::render();
    }
}
