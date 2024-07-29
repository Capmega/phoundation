<?php

/**
 * SidePanel class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->getModals()
             ->addModal('sign-in', new SignInModal());
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $sign_in = new SignInModal();
        $sign_in->useForm(true)
                ->getForm()
                ->setId('form-sign-in')
                ->setMethod(EnumHttpRequestMethod::post)
                ->setAction(Url::getAjax('sign-in'));
        $this->setMenu(Request::getMenusObject()
                              ->getPrimaryMenu())
             ->getModals()
             ->addModal('sign-in', $sign_in);

        return parent::render();
    }
}