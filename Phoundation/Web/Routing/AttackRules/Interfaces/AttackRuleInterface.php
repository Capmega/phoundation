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

namespace Phoundation\Web\Routing\AttackRules\Interfaces;

interface AttackRuleInterface
{
    /**
     * Executes the action for this attack rule
     *
     * @return static
     */
    public function executeAction(): static;
}