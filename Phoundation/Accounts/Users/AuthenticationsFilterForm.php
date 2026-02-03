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

        $this->o_definitions->get('date_range')->setSize(3);
        $this->o_definitions->get('users_id')->setSize(3);
        $this->o_definitions->get('status')->setSize(3);

        $this->o_definitions->add(Definition::new('action')
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
     * @param QueryBuilderInterface $o_builder
     *
     * @return static
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $o_builder): static
    {
        if ($this->o_applied_filters->keyExists('action') and $this->o_definitions->isRendered('action', false)) {
            if ($this->getAction()) {
                $o_builder->addWhere(
                    '`' . $o_builder->getFrom() . '`.`action` = :action', [':action' => $this->getAction()]
                );
            }
        }

        $this->o_applied_filters->removeKeys([
            'action',
        ]);

        return parent::applyFiltersToQueryBuilder($o_builder);
    }
}
