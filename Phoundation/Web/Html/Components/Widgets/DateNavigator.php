<?php

/**
 * Class DateNavigator
 *
 * Creates a date navigator HTML structure
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Data\Traits\TraitDataObjectDate;
use Phoundation\Date\PhoDateTime;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\InputDate;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Stringable;


class DateNavigator extends ElementsBlock
{
    use TraitDataObjectDate;


    /**
     * The link for the previous button
     *
     * @var string|null $prev_link
     */
    protected ?string $prev_link = null;

    /**
     * @var string|null $selector_link
     */
    protected ?string $selector_link = null;

    /**
     * The link for the next button
     *
     * @var string|null $next_link
     */
    protected ?string $next_link = null;

    /**
     * The prev button
     *
     * @var ButtonInterface|null $prev_button
     */
    protected ?ButtonInterface $prev_button = null;

    /**
     * The next button
     *
     * @var ButtonInterface|null $next_button
     */
    protected ?ButtonInterface $next_button = null;


    /**
     * DateNavigator class constructor
     *
     * @param string|null $source
     */
    public function __construct(?string $source = null)
    {
        parent::__construct($source);

        // Create the next button
        $this->next_button = Button::new();
        $this->prev_button = Button::new();
    }


    /**
     * Returns the prev button link
     *
     * @return string|null
     */
    public function getPrevLink(): ?string
    {
        return $this->prev_link;
    }


    /**
     * Sets the prev button link
     *
     * @param Stringable|string|null $link
     *
     * @return static
     */
    public function setPrevLink(Stringable|string|null $link): static
    {
        $this->prev_link = (string) $link;
        return $this;
    }


    /**
     * Returns the selector button link
     *
     * @return string|null
     */
    public function getSelectorLink(): ?string
    {
        return $this->selector_link;
    }


    /**
     * Sets the selector button link
     *
     * @param Stringable|string|null $link
     *
     * @return static
     */
    public function setSelectorLink(Stringable|string|null $link): static
    {
        $this->selector_link = (string) $link;
        return $this;
    }


    /**
     * Returns the next button link
     *
     * @return string|null
     */
    public function getNextLink(): ?string
    {
        return $this->next_link;
    }


    /**
     * Sets the next button link
     *
     * @param Stringable|string|null $link
     *
     * @return static
     */
    public function setNextLink(Stringable|string|null $link): static
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
        return ($this->next_button?->getDisabled() ?? false);
    }


    /**
     * Sets the next button link
     *
     * @param bool $disabled
     *
     * @return static
     */
    public function setNextDisabled(bool $disabled): static
    {
        $this->next_button?->setDisabled($disabled);
        return $this;
    }


    /**
     * Returns the prev button link
     *
     * @return bool
     */
    public function getPrevDisabled(): bool
    {
        return ($this->prev_button?->getDisabled() ?? false);
    }


    /**
     * Sets the prev button link
     *
     * @param bool $disabled
     *
     * @return static
     */
    public function setPrevDisabled(bool $disabled): static
    {
        $this->prev_button?->setDisabled($disabled);
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
                   ->addGridRow()
                   ->addGridColumn(GridColumn::new()
                                             ->setSize(2)
                                             ->addClasses('mb-3')
                                             ->setContent($this->prev_button
                                                              ?->setName('nav_prev')
                                                               ->setBlock(true)
                                                               ->setContent(tr('<'))
                                                               ->setMode(EnumDisplayMode::primary)
                                                               ->setAnchorUrl($this->prev_link)))

                   ->addGridColumn(GridColumn::new()
                                             ->setSize(8)
                                             ->addClasses('mb-3')
                                             ->setContent(Form::new()
                                                              ->setRequestMethod(EnumHttpRequestMethod::get)
                                                              ->setAction($this->selector_link)
                                                              ->setContent(InputDate::new()
                                                                                    ->setId('date')
                                                                                    ->setAutoSubmit(true)
                                                                                    ->addClasses('text-center')
                                                                                    ->setValue($this->o_date)
                                                                                    ->setMax(PhoDateTime::newToday('user')))))

                   ->addGridColumn(GridColumn::new()
                                             ->setSize(2)
                                             ->addClasses('mb-3')
                                             ->setContent($this->next_button
                                                              ?->setName('nav_next')
                                                               ->setBlock(true)
                                                               ->setContent(tr('>'))
                                                               ->setMode(EnumDisplayMode::primary)
                                                               ->setAnchorUrl($this->next_link)))

                   ->render();
    }
}
