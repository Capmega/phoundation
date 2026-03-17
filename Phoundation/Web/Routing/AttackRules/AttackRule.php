<?php

/**
 * Class AttackRule
 *
 * This class manages multiple entries from the table web_attack_rules
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Routing\AttackRules;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryComments;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryAction;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryExempt;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryExpression;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryMethod;
use Phoundation\Data\DataEntries\Traits\TraitDataEntrySeconds;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Requests\Exception\PageNotFoundException;
use Phoundation\Web\Routing\AttackRules\Interfaces\AttackRuleInterface;
use Phoundation\Web\Routing\Route;
use Plugins\Phoundation\Firewalls\Firewall;


class AttackRule extends DataEntry implements AttackRuleInterface
{
    use TraitDataEntrySeconds;
    use TraitDataEntryMethod;
    use TraitDataEntryUrl;
    use TraitDataEntryAction;
    use TraitDataEntryExpression;
    use TraitDataEntryExempt;
    use TraitDataEntryComments;


    /**
     * AttackRule class constructor
     *
     * @param IdentifierInterface|false|int|array|string|null $identifier
     * @param EnumLoadParameters|null $on_null_identifier
     * @param EnumLoadParameters|null $on_not_exists
     */
    public function __construct(IdentifierInterface|false|int|array|string|null $identifier = false, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null)
    {
        parent::__construct($identifier, $on_null_identifier, $on_not_exists);
        $this->setPermittedColumns('url');
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'web_attack_rules';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Web attack rules');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Executes the action for this attack rule
     *
     * @return static
     */
    public function executeAction(): static
    {
        $until    = $this->getSeconds() ? PhoDateTime::new()->addSeconds($this->getSeconds()) : null;
        $comments = ts('Request ":url" matched attack URL ":id :expression"', [
            ':url'        => $this->getUrl(),
            ':id'         => $this->getId(),
            ':expression' => $this->getExpression()
        ]);

        switch ($this->getAction()) {
            case 'block':
                // Add the IP to the firewall deny list
                Log::warning(ts('Blocking IP ":ip" for ":seconds" seconds because ":reason"', [
                    ':ip'      => Route::getRemoteIp(),
                    ':seconds' => $until ?? ts('permanent'),
                    ':reason'  => $comments,
                ]));

                Firewall::new()->deny(Route::getRemoteIp(), $until, comments: $comments);
                break;

            case 'deny-access':
                // Tell the client that access has been denied
                throw new AccessDeniedException($comments);

            case 'not-found':
                // Tell the client the page does not exist but do nothing else
                throw new PageNotFoundException($comments);

            case 'ignore':
                // Don't do anything, continue with the system as-is
                // no break
        }

        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->add(Definition::new('expression')
                                     ->setLabel(tr('Expression'))
                                     ->setSize(5)
                                     ->setMaxLength(255))

                     ->add(Definition::new('action')
                                     ->setLabel(tr('Action'))
                                     ->setSize(5)
                                     ->setSource([
                                         'blocked'       => tr('Blocked'),
                                         'access-denied' => tr('Access denied'),
                                         'ignored'       => tr('Ignored'),
                                         'other'         => tr('Other'),
                                     ]))

                     ->add(DefinitionFactory::newNumber('seconds')
                                            ->setLabel(tr('Time in seconds'))
                                            ->setSize(2))

                     ->add(DefinitionFactory::newComments());

        return $this;
    }
}
