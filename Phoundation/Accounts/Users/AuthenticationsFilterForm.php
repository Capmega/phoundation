<?php

/**
 * Class AuthenticationsFilterForm
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

use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Html\Enums\EnumElement;


class AuthenticationsFilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    public function __construct(?string $source = null)
    {
        $this->states = Authentication::getFilterStatuses();

        GetValidator::new()->setColumnDefault('all', 'status');

        parent::__construct($source);

        $this->_definitions->get('date_range')->setSize(3);
        $this->_definitions->get('users_id')->setSize(3);
        $this->_definitions->get('status')->setSize(3);

        $this->_definitions->add(Definition::new('action')
                                            ->setLabel(tr('Action'))
                                            ->setSize(3)
                                            ->setOptional(true)
                                            ->setElement(EnumElement::select)
                                            ->setKey(true, 'auto_submit')
                                            ->setSource(Authentication::getFilterActions()));
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
     * @param QueryBuilderInterface $_builder
     *
     * @return static
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $_builder): static
    {
        if ($this->_applied_filters->keyExists('action') and $this->_definitions->isRendered('action', false)) {
            if ($this->getAction()) {
                $_builder->addWhere(
                    '`' . $_builder->getFrom() . '`.`action` = :action', [':action' => $this->getAction()]
                );
            }
        }

        $this->_applied_filters->removeKeys([
            'action',
        ]);

        return parent::applyFiltersToQueryBuilder($_builder);
    }
}
