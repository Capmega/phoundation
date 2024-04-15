<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Data\Traits\TraitDataDate;
use Phoundation\Date\DateTime;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Input\Buttons\InputButton;
use Phoundation\Web\Html\Components\Input\InputDate;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Stringable;

/**
 * Class DateNavigator
 *
 * Creates a date navigator HTML structure
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class DateNavigator extends ElementsBlock
{
    use TraitDataDate;

    /**
     * The link for the previous button
     *
     * @var string $prev_link
     */
    protected string $prev_link;

    /**
     * @var string $selector_link
     */
    protected string $selector_link;

    /**
     * The link for the next button
     *
     * @var string $next_link
     */
    protected string $next_link;

    /**
     * The next button
     *
     * @var InputButton $next_button
     */
    protected InputButton $next_button;


    /**
     * DateNavigator class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        // Create the next button
        $this->next_button = InputButton::new();
    }


    /**
     * Returns the prev button link
     *
     * @return string
     */
    public function getPrevLink(): string
    {
        return $this->prev_link;
    }


    /**
     * Sets the prev button link
     *
     * @param Stringable|string $link
     *
     * @return $this
     */
    public function setPrevLink(Stringable|string $link): static
    {
        $this->prev_link = (string) $link;

        return $this;
    }


    /**
     * Returns the selector button link
     *
     * @return string
     */
    public function getSelectorLink(): string
    {
        return $this->selector_link;
    }


    /**
     * Sets the selector button link
     *
     * @param Stringable|string $link
     *
     * @return $this
     */
    public function setSelectorLink(Stringable|string $link): static
    {
        $this->selector_link = (string) $link;

        return $this;
    }


    /**
     * Returns the next button link
     *
     * @return string
     */
    public function getNextLink(): string
    {
        return $this->next_link;
    }


    /**
     * Sets the next button link
     *
     * @param Stringable|string $link
     *
     * @return $this
     */
    public function setNextLink(Stringable|string $link): static
    {
        $this->next_link = (string) $link;

        return $this;
    }


    /**
     * Returns the next button link
     *
     * @return bool
     */
    public function getNextDisabled(): bool
    {
        return $this->next_button->getDisabled();
    }


    /**
     * Sets the next button link
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setNextDisabled(bool $disabled): static
    {
        $this->next_button->setDisabled($disabled);

        return $this;
    }


    /**
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  ElementInterface::render()
     */
    public function render(): ?string
    {
        // Set up the tomorrow button. It may be disabled
        $this->next_button->setName('nav_next')
                          ->setMode(EnumDisplayMode::primary)
                          ->setBlock(true)
                          ->setContent(tr('>'))
                          ->setAnchorUrl($this->next_link);

        // Build the date selector
        return Grid::new()
                   ->addRow()
                   ->addColumn(GridColumn::new()
                                         ->setSize(2)
                                         ->addClasses('mb-3')
                                         ->setContent(InputButton::new()
                                                                 ->setName('nav_prev')
                                                                 ->setBlock(true)
                                                                 ->setContent(tr('<'))
                                                                 ->setMode(EnumDisplayMode::primary)
                                                                 ->setAnchorUrl($this->prev_link)
                                                                 ->render()))
                   ->addColumn(GridColumn::new()
                                         ->setSize(8)
                                         ->addClasses('mb-3')
                                         ->setContent(Form::new()
                                                          ->setMethod('get')
                                                          ->setAction($this->selector_link)
                                                          ->setContent(InputDate::new()
                                                                                ->setId('date')
                                                                                ->setAutoSubmit(true)
                                                                                ->addClasses('text-center')
                                                                                ->setValue($this->date)
                                                                                ->setMax(DateTime::getToday('user')))
                                                          ->render()))
                   ->addColumn(GridColumn::new()
                                         ->setSize(2)
                                         ->addClasses('mb-3')
                                         ->setContent($this->next_button->render()))
                   ->render();

//        return parent::render(); // TODO: Change the autogenerated stub
    }
}
