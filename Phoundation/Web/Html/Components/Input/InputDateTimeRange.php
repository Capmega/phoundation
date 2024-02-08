<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Traits\DataStartDateTime;
use Phoundation\Data\Traits\DataStopDate;
use Phoundation\Web\Html\Enums\EnumInputType;


/**
 * Class InputDateTimeRange
 *
 * Standard HTML date time range input control
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputDateTimeRange extends InputText
{
    use DataStartDateTime;
    use DataStopDate;


    /**
     * InputDate class constructor
     */
    public function __construct()
    {
        $this->type = EnumInputType::text;
        parent::__construct();
    }
}