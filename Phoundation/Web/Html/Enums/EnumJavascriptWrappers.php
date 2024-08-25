<?php

/**
 * Enum EnumJavascriptWrappers
 *
 * The different options to wrap javascript snippets
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumJavascriptWrappers: string
{
    case dom_content = 'dom_content'; // Wrap it within the window DOMContentLoaded event
    case function    = 'function';    // Wrap it within a self-executing function
    case window      = 'window';      // Wrap it within the window load event
    case ready       = 'ready';       // Wrap it with a jQuery document.ready
    case none        = 'none';        // No wrapping
}

