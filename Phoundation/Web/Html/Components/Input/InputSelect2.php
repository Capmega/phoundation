<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\InputType;
use function Phoundation\Web\Html\Components\Input\ready;


/**
 * Class InputSelect2
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputSelect2 extends InputSelect
{
    /**
     * InputSelect2 class constructor
     */
    public function __construct()
    {
        $this->type = $this->type ?? InputType::text;

        $(document).ready(function() {
            $('.js-example-basic-multiple').select2();
        });

        parent::__construct();
    }
}
