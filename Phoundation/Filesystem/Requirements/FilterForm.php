<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Requirements;

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Web\Html\Enums\EnumElement;


class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    /**
     * The different status values to filter on
     *
     * @var array $states
     */
    protected array $states;


    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Auto apply
        $this->applyValidator(self::class);
    }
}
