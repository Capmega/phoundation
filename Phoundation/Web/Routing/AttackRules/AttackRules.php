<?php

/**
 * Class AttackRules
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

use Phoundation\Accounts\Rights\Right;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataIterator;
use Phoundation\Web\Html\Components\P;
use Phoundation\Web\Requests\RequestLog;
use Phoundation\Web\Routing\AttackRules\Interfaces\AttackRuleInterface;
use Phoundation\Web\Routing\Route;


class AttackRules extends DataIterator
{
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
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return AttackRule::class;
    }


    /**
     * Returns the boolean value for the configuration path "security.web.attacks.enabled"
     *
     * @return bool
     */
    protected static function getConfigEnabled(): bool
    {
        return config()->getBoolean('security.web.attacks.enabled', true);
    }


    /**
     * Returns the boolean value for the configuration path "security.web.attacks.exempt.enabled"
     *
     * @return bool
     */
    protected static function getConfigAllowExempt(): bool
    {
        return config()->getBoolean('security.web.attacks.exempt.enabled', true);
    }


    /**
     * Returns the AttackRule object which expression matches the specified URL, or NULL if none matched
     *
     * @param string $url The URL to match
     *
     * @return AttackRuleInterface|null
     */
    public static function getMatch(string $url): ?AttackRuleInterface
    {
        if (AttackRules::getConfigEnabled()) {
            // Attack detection is enabled. Load rules and check each rule
            $rules = sql()->listKeyValues('SELECT `id`, `expression`, `exempt`, `action` FROM `web_attack_rules` WHERE `status` IS NULL');

            foreach ($rules as $rule) {
                if (preg_match($rule['expression'], $url)) {
                    Log::warning(ts('Request ":url" matches attack rule ":id" expression ":expression"', [
                        ':url' => $url,
                        ':id' => $rule['id'],
                        ':expression' => $rule['expression'],
                    ]));

                    if (preg_match($rule['exempt'], Route::getRemoteIp())) {
                        if (AttackRules::getConfigAllowExempt()) {
                            Log::warning(ts('Request ":url" came from IP address ":ip_address" which is exempt from this rule because ":exempt"', [
                                ':url' => $url,
                                ':ip_address' => Route::getRemoteIp(),
                                ':exempt' => $rule['exempt'],
                            ]));

                            continue;
                        }
                    }

                    return AttackRule::new($rule['id'])->setUrl($url);
                }
            }
        }

        return null;
    }
}
