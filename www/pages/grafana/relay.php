<?php

declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Network\Relay\Relay;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Relays grafana requests to grafana
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Grafana
 */
try {
    // Validate relay data and start building relay URL
    $validator = GetValidator::new()
        ->select('url')->hasMaxCharacters(255)->matchesRegex('/^\/[a-z0-9]+[a-z0-9-.\/]+/i')
        ->select('orgId')->isOptional()->isInteger()->isPositive()
        ->select('panelId')->isOptional()->isInteger()->isPositive()
        ->select('from')->isOptional()->isInteger()->isPositive()
        ->select('to')->isOptional()->isInteger()->isPositive();

    $get = $validator->validate(false);
    $url = Arrays::extractKey($get, 'url');

    if (str_starts_with($url, '/relay')) {
        // The /en/grafana/relay/d-solo/f86.... type URL's contain "relay/" which is not grafana, its this relay.
        // Remove it
        $url = str_replace('/relay', '', $url);
    }

    $url   = UrlBuilder::getWww('http://grafana.localhost:3000/grafana' . $url)->addQueries($get);
    $relay = Relay::new($url)->setPageReplace(['public/' => (string) UrlBuilder::getCdn('grafana/public/')]);

    $relay->getCurl()
        ->setFollowLocation(true)
        ->setOpt(CURLOPT_USERNAME, 'admin')
        ->setOpt(CURLOPT_PASSWORD, 'nX15/Qc410');

    $relay->get();

} catch (ValidationFailedException $e) {
    // Don't allow phoundation to catch this, as it will show validation error information that should be kept private
    // Just show the 400 page directly
    Page::execute('system/400');
}
