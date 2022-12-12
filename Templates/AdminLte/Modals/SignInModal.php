<?php

namespace Templates\AdminLte\Modals;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Html\Elements\Script;
use Phoundation\Web\Http\Url;
use Plugins\AdminLte\Components\Modal;
use Plugins\AdminLte\Forms\SignInForm;
use Plugins\AdminLte\Layouts\Grid;
use Plugins\AdminLte\Layouts\GridColumn;
use Plugins\AdminLte\Layouts\GridRow;



/**
 * MDB Plugin SignInModal class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\AdminLte
 */
class SignInModal extends Modal
{
    /**
     * The sign-in object
     *
     * @var SignInForm|null $form
     */
    protected ?SignInForm $form = null;



    /**
     * SignInModal class constructor
     */
    public function __construct()
    {
        $this->form = SignInForm::new();
        parent::__construct();
    }



    /**
     * Access the sign-in form
     *
     * @return SignInForm
     */
    public function getForm(): SignInForm
    {
        return $this->form;
    }



    /**
     * Render the HTML for this sign-in modal
     *
     * @return string
     */
    public function render(): string
    {
        // Build the form
        $form = $this->form->render();

        // Build the layout
        $layout = Grid::new()
            ->addRow(GridRow::new()
                ->addColumn(GridColumn::new()->setSize(3))
                ->addColumn(GridColumn::new()->setSize(6)->setContent($form))
                ->addColumn(GridColumn::new()->setSize(3))
            );

        // Set defaults
        $this->setId('signinModal')
            ->setSize('lg')
            ->setTitle(tr('Sign in'))
            ->setContent($layout->render());

        // Render the sign in modal.
        return parent::render() . Script::new()
            ->setContent('
            $("form#form-signin").submit(function(e) {
                e.stopPropagation();
                
                $.post("' . Url::build(Config::get('web.pages.signin', '/system/sign-in.html'))->ajax() . '", $(this).serialize())
                    .done(function (data, textStatus, jqXHR) {
                        $(".image-menu").replaceWith(data.html);
                        $("#signinModal").modal("hide");                     
                    });
                    
                return false;
            })
            ')
            ->render();
    }
}