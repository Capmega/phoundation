<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Traits\DataStartDate;
use Phoundation\Data\Traits\DataStopDate;
use Phoundation\Web\Html\Enums\EnumInputType;


/**
 * Class InputDateRangeButton
 *
 * Standard HTML date range input button control
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputDateRangeButton extends InputText
{
    use DataStartDate;
    use DataStopDate;


    /**
     * InputDateRangeButton class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->type = EnumInputType::text;
        parent::__construct($content);
    }
}