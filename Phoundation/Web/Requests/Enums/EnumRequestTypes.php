<?php

declare(strict_types=1);

namespace Phoundation\Web\Requests\Enums;

/**
 * Enum EnumRequestTypes
 *
 * This enum contains all the possible types of requests that can be made to the phoundation platform
 *
 * Sometimes it can be useful or required to know what kind of request we're processing. Is the request made by command
 * line or HTTP? We can use PLATFORM_CLI or PLATFORM_WEB for that too, but if we need more detail (Are we processing
 * an AJAX request or an API request?) we use Request::getRequestType() which will return the request type using this
 * EnumRequestTypes enum
 *
 * ADMIN:       HTML web pages in the /admin/ section
 *
 * AMP:         HTML web pages using googles godawful AMP stuff
 *
 * AJAX:        A client - server API type request, may be REST or GraphQL. Requires a cookie from the user through an
 *              HTML web page
 *
 * API:         An API type request that is made without cookies and requires its own authentication and session
 *              handling
 *
 * HTML:        Your standard HTML web page
 *
 * SYSTEM:      Any kind of special HTTP pages like 404, 500, 403, etc.
 *
 * CLI:         A request made by the command line
 *
 * UNSUPPORTED: Any kind of request that isn't covered by the above list and is not supported by the system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */
enum EnumRequestTypes: string
{
    case api         = 'api';
    case admin       = 'admin';
    case ajax        = 'ajax';
    case amp         = 'amp';
    case system      = 'system';
    case html        = 'html';
    case cli         = 'cli';
    case file        = 'file';
    case unsupported = 'unsupported';
    case unknown     = 'unknown';
}
