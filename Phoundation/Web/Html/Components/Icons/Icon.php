<?php

/**
 * Icon class
 *
 *
 * @see https://duncanlock.net/blog/2021/11/09/styleable-inline-svg-icon-sprite-system-with-caching-fallback/
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Icons;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataHeight;
use Phoundation\Data\Traits\TraitDataLabel;
use Phoundation\Data\Traits\TraitDataWidth;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\ElementCore;
use Phoundation\Web\Html\Components\Icons\Interfaces\IconInterface;
use Phoundation\Web\Html\Traits\TraitMode;


class Icon extends ElementCore implements IconInterface
{
    use TraitMode;
    use TraitDataWidth;
    use TraitDataHeight;
    use TraitDataLabel;

    /**
     * The vendor this icon belongs to
     * 
     * @var $vendor string|null 
     */
    protected ?string $vendor;

    /**
     * The subset this icon belongs to
     *
     * @var $subset string|null
     */
    protected ?string $subset;

    /**
     * The stroke width of this icon
     *
     * @var $stroke_width int|null
     */
    protected ?int $stroke_width;
    

    /**
     * Returns a new Icon object
     *
     * @param string|null $vendor
     * @param string|null $label
     *
     * @return static
     */
    public static function new(?string $vendor = null, ?string $label = null): static
    {
        return new static($vendor, $label);
    }


    /**
     * Icon class constructor
     *
     * @param string|null $vendor
     * @param string|null $label
     */
    public function __construct(?string $vendor = null, ?string $label = null)
    {
        $this->setLabel($label)
             ->setVendor($vendor);
        
        parent::__construct();
        $this->setElement('i');
    }


    /**
     * Returns the icon for this object
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->content;
    }


    /**
     * Sets the icon for this object
     *
     * @param string|null $icon
     * @param string      $subclass
     *
     * @return static
     */
    public function setIcon(?string $icon, string $subclass = ''): static
    {
        $this->content = $icon;
        return $this;
    }
    
    
    /**
     * Returns the Label for this Icon
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }


    /**
     * Sets the Label for this Icon
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabel(?string $label): static
    {
        if ($label) {
            $label = trim($label);
            if (!preg_match('/[a-z0-9-_ ]+/i', $label)) {
                // Icon names should only have letters, numbers and dashes and underscores. Multiple names may be
                // needed, so also allow spaces
                throw new OutOfBoundsException(tr('Invalid icon label ":label" specified', [
                    ':label' => $label,
                ]));
            }
        }

        $this->label = $label;
        return $this;
    }
    
    
    /**
     * Returns the Vendor for this Icon
     *
     * @return string|null
     */
    public function getVendor(): ?string
    {
        return $this->vendor;
    }


    /**
     * Sets the Vendor for this Icon
     *
     * @param string|null $vendor
     *
     * @return static
     */
    public function setVendor(?string $vendor): static
    {
        $this->vendor = $vendor;
        return $this;
    }

    
    /**
     * Returns the Subset for this Icon
     *
     * @return string|null
     */
    public function getSubset(): ?string
    {
        return $this->subset;
    }


    /**
     * Sets the Subset for this Icon
     *
     * @param string|null $subset
     *
     * @return static
     */
    public function setSubset(?string $subset): static
    {
throw new UnderConstructionException(tr('Subset is not yet supported for icons'));
        $this->subset = $subset;
        return $this;
    }


    /**
     * Sets the color of this icon by adding the relevant color classes
     *
     * @param string|null $color
     *
     * @return static
     */
    public function setColor(?string $color): static
    {
        $color = Strings::ensureStartsWith($color, 'color-');
        return $this->addClass($color);
    }

    
    /**
     * @return string|null
     */
    public function render(): ?string
    {
        switch ($this->getVendor()) {
            // TODO Currently, fas logic is hard coded.
            case 'fas':
                $this->addClasses(['fas', $this->getLabel(), 'fa-lg']);
                $this->setContent(null);

                break;

            default:
                Log::warning(tr('The Icon vendor ":vendor" is not yet supported or the Icon is being created improperly', [
                    ':vendor' => $this->getVendor()
                ]));
        }

        return parent::render();
    }
}
