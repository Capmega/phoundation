<?php

/**
 * Class AuthenticationsFilterForm
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

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Html\Enums\EnumElement;


class AuthenticationsFilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    public function __construct(?string $content = null)
    {
        $this->states = Authentication::getFilterStatuses();

        GetValidator::new()->setColumnDefault('all', 'status');

        parent::__construct($content);

        $this->definitions->get('date_range')->setSize(3);
        $this->definitions->get('users_id')->setSize(3);
        $this->definitions->get('status')->setSize(3);

        $this->definitions->add(Definition::new(null, 'action')
                                          ->setLabel(tr('Action'))
                                          ->setSize(3)
                                          ->setOptional(true)
                                          ->setElement(EnumElement::select)
                                          ->setKey(true, 'auto_submit')
                                          ->setDataSource(Authentication::getFilterActions()));

        // Auto apply
        $this->applyValidator(self::class);
    }


    /**
     * Returns the filtered action
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->get('action');
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $builder
     *
     * @return $this
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $builder): static
    {
        if ($this->getAction()) {
            $builder->addWhere(
                '`' . $builder->getFromTable() . '`.`action` = :action', [':action' => $this->getAction()]
            );
        }

        return parent::applyFiltersToQueryBuilder($builder);
    }
}
