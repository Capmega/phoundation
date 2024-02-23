<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Traits\DataDefinition;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormColumnInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Tooltip;
use Phoundation\Web\Html\Html;


/**
 * Class DataEntryComponentForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class DataEntryFormColumn implements DataEntryFormColumnInterface
{
    use DataDefinition;


    /**
     * The component (html or object) that needs to be rendered inside a form div
     *
     * @var RenderInterface|string|null $component
     */
    protected RenderInterface|string|null $component;


    /**
     * DataEntryFormColumn class constructor
     *
     * @param DefinitionInterface|null $definition
     */
    public function __construct(DefinitionInterface|null $definition = null)
    {
        $this->definition = $definition;
    }


    /**
     * Returns the component
     *
     * @param DefinitionInterface|null $definition
     * @return static
     */
    public static function new(DefinitionInterface|null $definition = null): static
    {
        return new static($definition);
    }


    /**
     * Returns the component
     *
     * @return RenderInterface|string|null
     */
    public function getComponent(): RenderInterface|string|null
    {
        return $this->component;
    }


    /**
     * Sets the component
     *
     * @param RenderInterface|string|null $component
     * @return static
     */
    public function setComponent(RenderInterface|string|null $component): static
    {
        $this->component = $component;
        return $this;
    }


    /**
     * Renders and returns the HTML for this component
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->definition) {
            throw new OutOfBoundsException(tr('Cannot render form component, no definition specified'));
        }

        if (!$this->component) {
            throw new OutOfBoundsException(tr('Cannot render form component, no component specified'));
        }

        $render     = '';
        $definition = $this->definition;

        if (is_object($this->component)) {
            $this->component = $this->component->render();
        }

        if ($definition->getHidden()) {
            // Hidden elements don't display anything beyond the hidden <input>
            return $this->component;
        }

        $render .= match ($definition->getInputType()?->value) {
            'checkbox' => '    <div class="col-sm-' . Html::safe($definition->getSize() ?? 12) . ($definition->getVisible() ? '' : ' invisible') . ($definition->getDisplay() ? '' : ' nodisplay') . '">
                                   <div class="form-group">
                                       <div class="form-horizontal">
                                           <label for="' . Html::safe($definition->getColumn()) . '">' . Html::safe($definition->getLabel()) . '</label>
                                           ' . $this->renderTooltip($definition) . '
                                       </div>
                                       <div class="form-check">
                                           ' . $this->component . '
                                           <label class="form-check-label" for="' . Html::safe($definition->getColumn()) . '">' . Html::safe($definition->getLabel()) . '</label>
                                       </div>
                                   </div>
                               </div>',

            default    => '    <div class="col-sm-' . Html::safe($definition->getSize() ?? 12) . ($definition->getVisible() ? '' : ' invisible') . ($definition->getDisplay() ? '' : ' nodisplay') . '">
                                   <div class="form-group">
                                       <div class="form-horizontal">
                                           <label for="' . Html::safe($definition->getColumn()) . '">' . Html::safe($definition->getLabel()) . '</label>
                                           ' . $this->renderTooltip($definition) . '
                                       </div>
                                       ' . $this->component . '
                                   </div>
                                </div>',
        };

        return $render;
    }


    /**
     * Renders and returns the tooltip for the specified definition
     *
     * @param DefinitionInterface $definition
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
