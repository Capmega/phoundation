<?php

/**
 * Class DataEntryFormColumn
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormColumnInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Tooltip;
use Phoundation\Web\Html\Html;

class DataEntryFormColumn extends ElementsBlock implements DataEntryFormColumnInterface
{
    /**
     * The component (html or object) that needs to be rendered inside a form div
     *
     * @var RenderInterface|string|null $column_component
     */
    protected RenderInterface|string|null $column_component;


    /**
     * Returns the component
     *
     * @return RenderInterface|string|null
     */
    public function getColumnComponent(): RenderInterface|string|null
    {
        return $this->column_component;
    }


    /**
     * Sets the component
     *
     * @param RenderInterface|string|null $column_component
     *
     * @return static
     */
    public function setColumnComponent(RenderInterface|string|null $column_component): static
    {
        $this->column_component = $column_component;
        return $this;
    }


    /**
     * Renders and returns the HTML for this component
     *
     * @return string|null
     */
    protected function defaultRender(): ?string
    {
        if (!$this->definition) {
            throw new OutOfBoundsException(tr('Cannot render form component, no definition specified'));
        }

        if (!$this->column_component) {
            throw new OutOfBoundsException(tr('Cannot render form component, no component specified'));
        }

        $scripts    = '';
        $definition = $this->definition;

        // Add scripts?
        if ($definition->getScripts()) {
            foreach ($definition->getScripts() as $script) {
                $scripts .= $script->render();
            }
        }

        if ($definition->getHidden()) {
            // Hidden elements don't display anything beyond the hidden <input>
            return $this->column_component . $scripts;
        }

        if ($this->column_component->hasOuterDiv()) {
            // Get attributes and properties for the outer div
            $outer      = $this->column_component->getOuterDiv();
            $class      = $outer->getClass();
            $attributes = $outer->getAttributes();
        }

        $this->render .= match ($definition->getInputType()?->value) {
            default => '  <div class="' . Html::safe($definition->getSize() ? 'col-sm-' . $definition->getSize() : 'col') . ($definition->getVisible() ? '' : ' invisible') . ($definition->getDisplay() ? '' : ' d-none') . (isset($class) ? ' ' . $class : '') . '"' . (isset($attributes) ? ' ' . $attributes : '') . '>
                                 <div data-mdb-input-init class="form-outline">
                                     ' . $this->column_component->render() . $scripts . '
                                     <label class="form-label" for="' . Html::safe($definition->getColumn()) . '">' . Html::safe($definition->getLabel()) . '</label>
                                 </div>
                             </div>',
            //            ' . $this->renderTooltip($definition) . '
        };

        return $this->render;
    }


    /**
     * Renders and returns the tooltip for the specified definition
     *
     * @param DefinitionInterface $definition
     *
     * @return string|null
     */
    protected function renderTooltip(DefinitionInterface $definition): ?string
    {
        if ($definition->getTooltip()) {
            // Render and return the tooltip
            return Tooltip::new()
                          ->setTitle($definition->getTooltip())
                          ->setUseIcon(true)
                          ->render();
        }

        return null;
    }
}
