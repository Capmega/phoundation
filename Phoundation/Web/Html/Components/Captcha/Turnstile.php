<?php

/**
 * Class Turnstile
 *
 * Captcha system based on Cloudflare Turnstile.
 *
 * @see       https://www.cloudflare.com/products/turnstile/
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Captcha;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Network\Curl\Post;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Script;


class Turnstile extends Captcha
{
    /**
     * Script used for this Turnstile object
     *
     * @var string $script
     */
    protected string $script = '';


    /**
     * Returns the script required for this Turnstile
     *
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }


    /**
     * Returns true if the token is valid for the specified action
     *
     * @param string|null $response
     * @param string|null $remote_ip
     * @param string|null $secret
     *
     * @return void
     */
    public function validateResponse(?string $response, string $remote_ip = null, string $secret = null): void
    {
        if (!$this->isValid($response, $remote_ip, $secret)) {
            throw new ValidationFailedException(tr('The Turnstile response is invalid for ":remote_ip"', [
                ':remote_ip' => $remote_ip ?? $_SERVER['REMOTE_ADDR'],
            ]));
        }
    }


    /**
     * Returns true if the token is valid for the specified action
     *
     * @param string|null $response
     * @param string|null $remote_ip
     * @param string|null $secret
     *
     * @return bool
     */
    public function isValid(?string $response, string $remote_ip = null, string $secret = null): bool
    {
        if (!$response) {
            // There is no response, this is failed before we even begin
            Log::warning(tr('No captcha client response received'));

            return false;
        }
        // Get captcha secret key
        if (!$secret) {
            // Use configured secret key
            if (Core::isProductionEnvironment()) {
                $secret = Config::getString('security.web.captcha.turnstile.secret');

            } else {
                // This is a test key, should only be used in non production environments
                $secret = '';
            }
        }
        if (!$remote_ip) {
            // Default to the IP address of this client
            // TODO This might cause issues with reverse proxies, look into that later
            $remote_ip = $_SERVER['REMOTE_ADDR'];
        }
        // Check with Google if captcha passed or not
        $post = Post::new('')
                    ->setPostUrlEncoded(true)
                    ->addPostValues([
                        'secret'    => $secret,
                        'response'  => $response,
                        'remote_ip' => $remote_ip,
                    ])
                    ->execute();
        $response = $post->getResultData();
        $response = Json::decode($response);
        $response = Strings::toBoolean($response['success']);
        if ($response) {
            Log::success(tr('Passed Turnstile CAPTCHA test'));
        } else {
            Log::warning(tr('Failed Turnstile CAPTCHA test'));
        }

        return $response;
    }


    /**
     * Renders and returns the HTML for the google ReCAPTCHA
     *
     * @return string
     */
    public function render(): string
    {
        // Get captcha public key
        // TODO: Change this to some testing mode, taken from Core
        if (Core::isProductionEnvironment()) {
            $key = Config::getString('security.web.captcha.turnstile.key');
        } else {
            // This is a test key, should only be used in non production environments
            $key = '';
        }

        return Script::new()
                     ->setAsync(true)
                     ->setDefer(true)
                     ->setSrc($this->script)
                     ->render() . '<div class="" data-sitekey="' . $key . '"></div>';
    }
}
