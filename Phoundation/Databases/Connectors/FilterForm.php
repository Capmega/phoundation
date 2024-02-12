<?php

declare(strict_types=1);

namespace Phoundation\Databases\Connectors;

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Enums\EnumInputElement;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class FilterForm extends \Phoundation\Web\Html\Components\FilterForm
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

        $this->states = [
            '__all'   => tr('All'),
            null      => tr('Active'),
            'locked'  => tr('Locked'),
            'deleted' => tr('Deleted'),
        ];

        $connector = Connector::new();

        $this->definitions = Definitions::new()
            ->add(Definition::new(null, 'entry_status')
                ->setLabel(tr('Status'))
                ->setSize(4)
                ->setOptional(true)
                ->setElement(EnumInputElement::select)
                ->setValue(isset_get($this->source['entry_status']))
                ->setKey(true, 'auto_submit')
                ->setDataSource($this->states))
            ->add($connector->getDefinitions()->get('type'));
    }
}
