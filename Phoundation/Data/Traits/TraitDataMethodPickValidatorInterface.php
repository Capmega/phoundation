<?php

/**
 * Trait TraitDataMethodPickValidatorInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;


trait TraitDataMethodPickValidatorInterface
{
    /**
     * Returns the required validator, depending on the specified source
     *
     * @param ValidatorInterface|array|null &$source      The optional source data to use for the selected Validator object
     * @param bool                           $direct_mode If true, will enable direct-mode for the Validator object
     *
     * @return ValidatorInterface
     */
    public static function pick(ValidatorInterface|array|null &$source = null, bool $direct_mode = false): ValidatorInterface
    {
        // Determine data-source for this modification
        if ($source === null) {
            // Use default data depending on platform
            if (PLATFORM_WEB) {
                return PostValidator::new()->setDirectMode($direct_mode);
            }

            // This is the default for the CLI platform
            return ArgvValidator::new()->setDirectMode($direct_mode);
        }

        if (is_object($source)) {
            // The specified data source is a DataValidatorInterface type validator
            return $source->setDirectMode($direct_mode);
        }

        // Data source is an array, put it in an ArrayValidator.
        return ArrayValidator::new($source)->setDirectMode($direct_mode);
    }
}
