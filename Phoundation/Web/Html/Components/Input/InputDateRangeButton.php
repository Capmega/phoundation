<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\InputType;
use Plugins\Medinet\Traits\DataStartDate;
use Plugins\Medinet\Traits\DataStopDate;


/**
 * Class InputDateRangeButton
 *
 * Standard HTML date range input button control
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputDateRangeButton extends InputText
{
    use DataStartDate;
    use DataStopDate;


    /**
     * InputDateRangeButton class constructor
     */
    public function __construct()
    {
        $this->type = InputType::text;
        parent::__construct();
    }
}