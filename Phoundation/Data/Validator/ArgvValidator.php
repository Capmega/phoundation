<?php

namespace Phoundation\Data\Validator;



/**
 * ArgvValidator class
 *
 * This class validates data from untrusted $argv
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class ArgvValidator extends Validator
{
    /**
     * Internal $argv array until validation has been completed
     *
     * @var array|null $argv
     */
    protected static ?array $argv = null;




    /**
     * Validator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param Validator|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(?Validator $parent = null) {
        $this->source = &self::$argv;
        $this->parent = $parent;
    }



    /**
     * Returns a new array validator
     *
     * @param Validator|null $parent
     * @return static
     */
    public static function new(?Validator $parent = null): static
    {
        return new static($parent);
    }



    /**
     * Link $_GET and $argv and $argv data to internal arrays to ensure developers cannot access them until validation
     * has been completed
     *
     * @return void
     */
    public static function hideData(): void
    {
        global $argv;

        // Copy $argv data and reset the global $argv
        self::$argv = $argv;

        $argv     = [];
    }



    /**
     * Gives free and full access to $argv data, now that it has been validated
     *
     * @return void
     */
    protected static function liberateData(): void
    {
        global $argv;
        $argv = self::$argv;
        self::$argv = null;
    }
}