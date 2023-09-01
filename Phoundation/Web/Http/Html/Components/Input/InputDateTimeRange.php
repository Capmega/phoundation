<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Enums\InputType;
use Plugins\Medinet\Traits\DataStartDate;
use Plugins\Medinet\Traits\DataStartDateTime;
use Plugins\Medinet\Traits\DataStopDate;


/**
 * Class InputDateTimeRange
 *
 * Standard HTML date time range input control
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        $this->type = InputType::text;
        parent::__construct();
    }
}