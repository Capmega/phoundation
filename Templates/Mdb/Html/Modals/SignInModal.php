<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Modals;

use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Forms\SignInForm;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Html\Layouts\GridRow;
use Phoundation\Web\Http\UrlBuilder;
use Templates\Mdb\Html\Components\Modal;


/**
 * MDB Plugin SignInModal class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class SignInModal extends Modal
{
    /**
     * Table class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\Modals\SignInModal $element)
    {
        parent::__construct($element);
        $this->render_object->setForm(SignInForm::new());
    }


    /**
     * Render the HTML for this sign-in modal
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Build the form
        $form = $this->render_object->getForm()->render();
        $this->render_object->setForm(null);

        // Build the layout
        $layout = Grid::new()
            ->addRow(GridRow::new()
                ->addColumn(GridColumn::new()->setSize(DisplaySize::three))
                ->addColumn(GridColumn::new()->setSize(DisplaySize::six)->setContent($form))
                ->addColumn(GridColumn::new()->setSize(DisplaySize::three))
            );

        // Set defaults
        $this->render_object
            ->setId('signinModal')
            ->setSize('lg')
            ->setTitle(tr('Sign in'))
            ->setContent($layout->render());

        // Render the sign in modal.
        return parent::render() . Script::new()->setContent('
            $("form#form-sign-in").submit(function(e) {
                e.stopPropagation();
                
                $.post("' . UrlBuilder::getAjax('sign-in') . '", $(this).serialize())
                    .done(function (data, textStatus, jqXHR) {
                        $(".image-menu").replaceWith(data.profileImage);
                        $("#top-menu").replaceWith(data.topMenu);
                        $("#signinModal").modal("hide");                     
                    });
                    
                return false;
            })
            ')
            ->render();
    }
}