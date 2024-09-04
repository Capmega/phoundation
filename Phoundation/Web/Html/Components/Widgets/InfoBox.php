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
use Phoundation\Data\Traits\TraitDataContent;
use Phoundation\Data\Traits\TraitDataCurrentFloat;
use Phoundation\Data\Traits\TraitDataLabel;
use Phoundation\Data\Traits\TraitDataMaximumInteger;
use Phoundation\Data\Traits\TraitDataMinimumInteger;
use Phoundation\Data\Traits\TraitDataTitle;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Widgets\Interfaces\ProgressBarInterface;


class InfoBox extends Widget
{
    use TraitDataBackgroundColor;
    use TraitDataTitle;
    use TraitDataContent;


    /**
     * InfoBox class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setBackgroundColor('light');
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        return  '   <div class="info-box bg-' . $this->background_color . '">
                        <div class="info-box-content">
                            <span class="info-box-text text-center text-muted">' . $this->title . '</span>
                            <span class="info-box-number text-center text-muted mb-0">' . $this->content . '</span>
                        </div>
                    </div>';
    }
}
