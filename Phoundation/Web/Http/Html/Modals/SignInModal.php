<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Modals;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Html\Components\Modal;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Forms\SignInForm;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Html\Layouts\GridRow;
use Phoundation\Web\Http\UrlBuilder;


/**
 * SignInModal class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class SignInModal extends Modal
{
    /**
     * SignInModal class constructor
     */
    public function __construct()
    {
        $this->setContent(SignInForm::new());
        parent::__construct();
    }


    /**
     * Render the HTML for this sign-in modal
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Build the form
        $form = $this->form->render();

        // Build the layout
        $layout = Grid::new()
            ->addRow(GridRow::new()
                ->addColumn(GridColumn::new()->setSize(DisplaySize::three))
                ->addColumn(GridColumn::new()->setSize(DisplaySize::six)->setContent($form))
                ->addColumn(GridColumn::new()->setSize(DisplaySize::three))
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
                
                $.post("' . UrlBuilder::getAjax(Config::get('web.pages.signin', '/system/sign-in.html')) . '", $(this).serialize())
                    .done(function (data, textStatus, jqXHR) {
                        $(".image-menu").replaceWith(data.html);
                        $("#signinModal").modal("hide");                     
                    });
                    
                return false;
            })')->render();
    }
}