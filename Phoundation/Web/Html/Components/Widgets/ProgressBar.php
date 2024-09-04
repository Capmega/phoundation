<?php

/**
 * ProgressBar class
 *
 * This HTML widget component object can render the HTML required to display a progress bar
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Data\Traits\TraitDataBackgroundColor;
use Phoundation\Data\Traits\TraitDataCurrentFloat;
use Phoundation\Data\Traits\TraitDataCurrentInteger;
use Phoundation\Data\Traits\TraitDataLabel;
use Phoundation\Data\Traits\TraitDataMaximumInteger;
use Phoundation\Data\Traits\TraitDataMinimumInteger;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Widgets\Interfaces\ProgressBarInterface;


class ProgressBar extends Widget implements ProgressBarInterface
{
    use TraitDataBackgroundColor;
    use TraitDataCurrentFloat;
    use TraitDataLabel;
    use TraitDataMaximumInteger;
    use TraitDataMinimumInteger;


    /**
     * ProgressBar class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setBackgroundColor('blue')
             ->setLabel(tr(':current% Completed'))
             ->setMinimum(0)
             ->setMaximum(100)
             ->setCurrent(0);
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $width = ($this->current / ($this->maximum - $this->minimum)) * 100;

        return  '<div class="progress progress-sm">
                     <div class="progress-bar bg-' . $this->background_color . '" role="progressbar" aria-valuenow="' . $this->current . '" aria-valuemin="' . $this->minimum . '" aria-valuemax="' . $this->maximum . '" style="width: ' . $width . '%">
                     </div>
                 </div>
                 <small>' . str_replace(':current', (string) $this->current, $this->label) . '</small>';
    }
}
