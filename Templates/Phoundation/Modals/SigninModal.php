<?php

namespace Templates\Phoundation\Modals;

use Plugins\Mdb\Elements\Buttons;
use Plugins\Mdb\Components\Modal;
use Plugins\Mdb\Forms\Signin;
use Plugins\Mdb\Layouts\Grid;
use Plugins\Mdb\Layouts\GridColumn;
use Plugins\Mdb\Layouts\GridRow;



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
        // Build the form
        $form = Signin::new()->render();

        // Build the layout
        $layout = Grid::new()
            ->addRow(GridRow::new()
                ->addColumn(GridColumn::new(3))
                ->addColumn(GridColumn::new(6)->setContent($form))
                ->addColumn(GridColumn::new(3))
            );

        // Set defaults
        $this->setId('signinModal')
            ->setSize('lg')
            ->setTitle(tr('Sign in'))
            ->setContent($layout->render());

        return parent::__construct();
    }
}