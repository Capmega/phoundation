<?php

namespace Templates\Phoundation\Modals;

use Plugins\Mdb\Elements\Button;
use Plugins\Mdb\Elements\Buttons;
use Plugins\Mdb\Modal;



/**
 * MDB Plugin SigninModal class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class SigninModal extends Modal
{
    /**
     * SigninModal class constructor
     */
    public function __construct()
    {
        // Set defaults
        $this->setId('signinModal')
            ->setTitle(tr('Sign in'))
            ->setButtons(Buttons::new()
                ->addButton(Button::new()
                    ->setContent(tr('Signin'))))
            ->setContent('BLERGH!');

        return parent::__construct();
    }
}