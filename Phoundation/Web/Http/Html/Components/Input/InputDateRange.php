<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Page;
use Plugins\Medinet\Traits\DataStartDate;
use Plugins\Medinet\Traits\DataStopDate;


/**
 * Class InputDateRange
 *
 * Standard HTML date range input control
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputDateRange extends InputText
{
    use DataStartDate;
    use DataStopDate;


    /**
     * InputDateRange class constructor
     */
    public function __construct()
    {
        $this->type = InputType::text;
        parent::__construct();
    }


    /**
     * Render and return the HTML for this Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Required javascript
        Page::loadJavascript('adminlte/plugins/moment/moment');
        Page::loadJavascript('adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4');
        Page::loadJavascript('adminlte/plugins/daterangepicker/daterangepicker');

        return parent::render();
    }
}