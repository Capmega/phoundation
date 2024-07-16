<?php

/**
 * MetaModal class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Modals;

use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Html\Layouts\GridRow;
use Phoundation\Web\Http\UrlBuilder;

class MetaModal extends Modal
{
    /**
     * SignInModal class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setContent('Hello!');
        parent::__construct($content);
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
                                      ->addColumn(GridColumn::new()
                                                            ->setSize(EnumDisplaySize::three))
                                      ->addColumn(GridColumn::new()
                                                            ->setSize(EnumDisplaySize::six)
                                                            ->setContent($form))
                                      ->addColumn(GridColumn::new()
                                                            ->setSize(EnumDisplaySize::three)));
        // Set defaults
        $this->setId('MetaModal')
             ->setSize('lg')
             ->setTitle(tr('Audit information'))
             ->setContent($layout->render());

        // Render the sign in modal.
        return parent::render() . Script::new()
                                        ->setContent('
            $("table.showmeta").click(function(e) {
                e.stopPropagation();

                $.get("' . UrlBuilder::getAjax('system/meta/') . '" + id + ".html")
                    .done(function (data, textStatus, jqXHR) {
                        $("#MetaModal").find("").innerHtml(data.html);                     
                    });
                // Load the meta-information here                
                    
                return false;
            })')
                                        ->render();
    }
}