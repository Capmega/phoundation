<?php

/**
 * Enum EnumTitleCaseStyles
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 * @see       https://en.wikipedia.org/wiki/Title_case
 */


declare(strict_types=1);

namespace Phoundation\Utils\Enums;


enum EnumTitleCaseStyles: string
{
    case ApStylebook                                                = 'ApStylebook';
    case ChicagoManualOfStyle                                       = 'ChicagoManualOfStyle';
    case ModernLanguageAssociationHandbook                          = 'ModernLanguageAssociationHandbook';
    case ApaStyle                                                   = 'ApaStyle';
    case AmericanMedicalAssociationManualOfStyleCapitalizationRules = 'AmericanMedicalAssociationManualOfStyleCapitalizationRules';
    case BlueBook                                                   = 'BlueBook';
}
