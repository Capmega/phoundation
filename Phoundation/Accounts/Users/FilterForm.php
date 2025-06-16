<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Utils\Json;
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

        $this->o_definitions->add(Definition::new('roles_id')
                                            ->setLabel(tr('Role'))
                                            ->setSize(4)
                                            ->setOptional(true)
                                            ->setElement(EnumElement::select)
                                            ->setInputType(EnumInputType::dbid)
                                            ->setContent(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                              return Roles::new()
                                                          ->getHtmlSelectOld()
                                                          ->setAutoSubmit(true)
                                                          ->setName($field_name)
                                                          ->setNotSelectedLabel(tr('All'))
                                                          ->setSelected(isset_get($this->source[$key]));
                                          }))

                          ->add(Definition::new('rights_id')
                                          ->setLabel(tr('Right'))
                                          ->setSize(4)
                                          ->setOptional(true)
                                          ->setElement(EnumElement::select)
                                          ->setInputType(EnumInputType::dbid)
                                          ->setContent(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                              return Rights::new()
                                                           ->getHtmlSelectOld()
                                                           ->setAutoSubmit(true)
                                                           ->setName($field_name)
                                                           ->setNotSelectedLabel(tr('All'))
                                                           ->setSelected(isset_get($this->source[$key]));
                                          }));

        $this->o_definitions->get('date_range')->setRender(false);
        $this->o_definitions->get('users_id')->setRender(false);
        $this->o_definitions->get('status')->setSize(4);

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
        return Role::new()->loadNull($this->getRolesId());
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
        return Right::new()->loadNull($this->getRolesId());
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $builder
     *
     * @return static
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $builder): static
    {
        if ($this->apply_filters->keyExists('roles_id') and $this->o_definitions->isRendered('roles_id', false)) {
            if ($this->getRolesId()) {
                $builder->addJoin('JOIN  `accounts_users_roles` AS `accounts_users_roles_filter`
                                   ON    `accounts_users_roles_filter`.`roles_id` = :roles_id
                                     AND `accounts_users_roles_filter`.`users_id`  = `accounts_users`.`id` ', [
                                         ':roles_id' => $this->getRolesId()]);
            }
        }

        if ($this->apply_filters->keyExists('rights_id') and $this->o_definitions->isRendered('rights_id', false)) {
            if ($this->getRightsId()) {
                $builder->addJoin('JOIN  `accounts_users_rights` AS `accounts_users_rights_filter`
                                   ON    `accounts_users_rights_filter`.`rights_id` = :rights_id
                                     AND `accounts_users_rights_filter`.`users_id`  = `accounts_users`.`id` ', [
                                         ':rights_id' => $this->getRightsId()]);
            }
        }

        $this->apply_filters->removeKeys([
            'roles_id',
            'rights_id',
        ]);

        return parent::applyFiltersToQueryBuilder($builder);
    }
}
