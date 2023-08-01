<?php

declare(strict_types=1);

namespace Phoundation\Network\Proxies;

use Phoundation\Core\Strings;
use Phoundation\Network\Exception\NetworkException;
use Phoundation\Utils\Json;


/**
 * Class Proxies
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class Proxies
{
    public static function list($url, $file = '', $restrictionsurl = null): array
    {
        if (!$restrictionsurl) {
            $restrictionsurl = $_CONFIG['curl']['proxies'];
        }

        if (is_array($restrictionsurl)) {
            $restrictionsurl = array_random_value($restrictionsurl);
        }

        if (is_array($url)) {
            throw new NetworkException(tr('No URL specified'));
        }

        if (!$restrictionsurl) {
            throw new NetworkException(tr('No proxy server URL(s) specified'));
        }

        log_console(tr('Using proxy ":proxy"', array(':proxy' => Strings::cut(Strings::Log($restrictionsurl), '://', '/'))), 'VERBOSE');

        $data = curl_get(array('url'        => Strings::endsWith($restrictionsurl, '?apikey='.$_CONFIG['curl']['apikey'].'&url=').urlencode($url),
            'getheaders' => false,
            'proxies'    => false));

        if (!trim($data['data'])) {
            throw new NetworkException(tr('Proxy returned no data. Is proxy correctly configured? Proxy domain resolves correctly?'));
        }

        if (!str_starts_with($data['data'], 'PROXY_RESULT')) {
            throw new NetworkException(tr('curl_get_proxy(): Proxy returned invalid data ":data" from proxy ":proxy". Is proxy correctly configured? Proxy domain resolves correctly?', [
                ':data'  => Strings::Log($data),
                ':proxy' => Strings::cut(Strings::Log($restrictionsurl), '://', '/')
            ]));
        }

        $data         = substr($data['data'], 12);
        $data         = Json::decode($data);
        $data['data'] = base64_decode($data['data']);

        if ($file) {
            /*
             * Write the data to the specified file
             */
            file_put_contents($file, $data['data']);
        }

        return $data;
    }

    

    /**
     * @param $url
     * @param $file
     * @param $restrictionsurl
     * @return Proxy
     */
    public static function getRandom($url, $file = '', $restrictionsurl = null): Proxy
    {
        global $_CONFIG;

        try {
            if (!$restrictionsurl) {
                $restrictionsurl = $_CONFIG['curl']['proxies'];
            }

            if (is_array($restrictionsurl)) {
                $restrictionsurl = array_random_value($restrictionsurl);
            }

            if (is_array($url)) {
                throw new NetworkException(tr('No URL specified'));
            }

            if (!$restrictionsurl) {
                throw new NetworkException(tr('No proxy server URL(s) specified'));
            }

            log_console(tr('Using proxy ":proxy"', array(':proxy' => Strings::cut(Strings::Log($restrictionsurl), '://', '/'))), 'VERBOSE');

            $data = curl_get(array('url'        => Strings::endsWith($restrictionsurl, '?apikey='.$_CONFIG['curl']['apikey'].'&url=').urlencode($url),
                'getheaders' => false,
                'proxies'    => false));

            if (!trim($data['data'])) {
                throw new NetworkException(tr('Proxy returned no data. Is proxy correctly configured? Proxy domain resolves correctly?'));
            }

            if (!str_starts_with($data['data'], 'PROXY_RESULT')) {
                throw new NetworkException(tr('curl_get_proxy(): Proxy returned invalid data ":data" from proxy ":proxy". Is proxy correctly configured? Proxy domain resolves correctly?', [
                    ':data'  => Strings::Log($data),
                    ':proxy' => Strings::cut(Strings::Log($restrictionsurl), '://', '/')
                ]));
            }

            $data         = substr($data['data'], 12);
            $data         = json_decode_custom($data);
            $data['data'] = base64_decode($data['data']);

            if ($file) {
                /*
                 * Write the data to the specified file
                 */
                file_put_contents($file, $data['data']);
            }

            return $data;
    }
}