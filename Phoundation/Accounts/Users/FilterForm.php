<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;


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
        $this->states = [
            'all'     => tr('All'),
            null      => tr('Active'),
            'deleted' => tr('Deleted'),
        ];

        parent::__construct();

        $this->definitions->add(Definition::new(null, 'roles_id')
                                          ->setLabel(tr('Role'))
                                          ->setSize(4)
                                          ->setOptional(true)
                                          ->setElement(EnumElement::select)
                                          ->setInputType(EnumInputType::dbid)
                                          ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                              return Roles::new()
                                                          ->getHtmlSelect()
                                                          ->setAutoSubmit(true)
                                                          ->setName($field_name)
                                                          ->setNotSelectedLabel(tr('All'))
                                                          ->setSelected(isset_get($this->source[$key]));
                                          }))

                          ->add(Definition::new(null, 'rights_id')
                                          ->setLabel(tr('Right'))
                                          ->setSize(4)
                                          ->setOptional(true)
                                          ->setElement(EnumElement::select)
                                          ->setInputType(EnumInputType::dbid)
                                          ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                              return Rights::new()
                                                           ->getHtmlSelect()
                                                           ->setAutoSubmit(true)
                                                           ->setName($field_name)
                                                           ->setNotSelectedLabel(tr('All'))
                                                           ->setSelected(isset_get($this->source[$key]));
                                          }));

        $this->definitions->get('date_range')->setRender(false);
        $this->definitions->get('users_id')->setRender(false);
        $this->definitions->get('status')->setSize(4);

        // Auto apply
        $this->applyValidator(self::class);
    }


    /**
     * Returns the filtered roles id
     *
     * @return int|null
     */
    public function getRolesId(): ?int
    {
        return get_null((int) $this->get('roles_id'));
    }


    /**
     * Returns the filtered role
     *
     * @return RoleInterface|null
     */
    public function getRole(): ?RoleInterface
    {
        return Role::loadOrNull($this->getRolesId());
    }


    /**
     * Returns the filtered rights id
     *
     * @return int|null
     */
    public function getRightsId(): ?int
    {
        return get_null((int) $this->get('rights_id'));
    }


    /**
     * Returns the filtered right
     *
     * @return RightInterface|null
     */
    public function getRight(): ?RightInterface
    {
        return Right::loadOrNull($this->getRolesId());
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $query_builder
     *
     * @return $this
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $query_builder): static
    {
        if ($this->get('rights_id')) {
            $query_builder
                ->addJoin('JOIN  `accounts_roles_rights` AS `accounts_roles_rights_filter`
                           ON    `accounts_roles_rights_filter`.`rights_id` = :rights_id
                             AND `accounts_roles_rights_filter`.`roles_id`  = `accounts_roles`.`id` ', [
                    ':rights_id' => $this->get('rights_id'),
                ]);
        }

        if ($this->get('roles_id')) {
            $query_builder
                ->addJoin('JOIN  `accounts_roles_rights` AS `accounts_roles_rights_filter`
                           ON    `accounts_roles_rights_filter`.`roles_id`  = :roles_id
                             AND `accounts_roles_rights_filter`.`rights_id` = `accounts_rights`.`id` ', [
                    ':roles_id' => $this->get('roles_id'),
                ]);
        }

        return parent::applyFiltersToQueryBuilder($query_builder);
    }
}
