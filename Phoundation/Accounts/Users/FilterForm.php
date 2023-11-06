<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Enums\InputElement;
use Phoundation\Web\Http\UrlBuilder;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class FilterForm extends \Phoundation\Web\Http\Html\Components\FilterForm
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

        $this->definitions = Definitions::new()
            ->addDefinition(Definition::new(null, 'entry_status')
                ->setLabel(tr('Status'))
                ->setSize(4)
                ->setOptional(true)
                ->setElement(InputElement::select)
                ->setValue(isset_get($this->source['entry_status']))
                ->setKey(true, 'auto_submit')
                ->setSource($this->states))
            ->addDefinition(Definition::new(null, 'roles_id')
                ->setLabel(tr('Role'))
                ->setSize(4)
                ->setOptional(true)
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Roles::new()->getHtmlSelect()
                        ->setAutoSubmit(true)
                        ->setName($field_name)
                        ->setNone(tr('Select'))
                        ->setSelected(isset_get($this->source[$key]))
                        ->render();
                }))
            ->addDefinition(Definition::new(null, 'rights_id')
                ->setLabel(tr('Right'))
                ->setSize(4)
                ->setOptional(true)
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Rights::new()->getHtmlSelect()
                        ->setAutoSubmit(true)
                        ->setName($field_name)
                        ->setNone(tr('Select'))
                        ->setSelected(isset_get($this->source[$key]))
                        ->render();
                }));
    }
}
