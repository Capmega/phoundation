<?php

/**
 * Command network public-ip
 *
 * This command will detect and display the public IP address
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Network\Exception\NetworkException;
use Phoundation\Network\Network;


CliDocumentation::setUsage('./pho network public-ip');

CliDocumentation::setHelp('This command will detect and display the public IP address


ARGUMENTS


-');


// Get arguments
$argv = ArgvValidator::new()->validate();


try {
    // Display the public IP address
    Log::cli(Network::getPublicIpAddress());

} catch (NetworkException $e) {
    throw NetworkException::new(tr('Failed to detect public IP address, see logs for more information'), $e)->makeWarning();
}
