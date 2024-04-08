<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Captcha;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Exception\CaptchaFailedException;
use Phoundation\Network\Curl\Post;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Script;

/**
 * Class ReCaptcha
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class ReCaptcha2 extends Captcha
{
    /**
     * Captcha size
     *
     * @var string $size
     */
    #[ExpectedValues('normal', 'compact')]
    protected string $size = 'normal';

    /**
     * Script used for this ReCaptcha object
     *
     * @var string $script
     */
    protected string $script = 'https://www.google.com/recaptcha/api.js';


    /**
     * Returns the recaptcha size
     *
     * @return string
     */
    #[ExpectedValues('normal', 'compact')]
    public function getSize(): string
    {
        return $this->size;
    }


    /**
     * Sets the captcha size
     *
     * @param string $size
     *
     * @return ReCaptcha2
     */
    public function setSize(#[ExpectedValues('normal', 'compact')] string $size): static
    {
        $this->size = $size;

        return $this;
    }


    /**
     * Returns the script required for this ReCaptcha
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
            throw new CaptchaFailedException(tr('The ReCaptcha response is invalid for ":remote_ip"', [
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
                $secret = Config::getString('security.web.captcha.recaptcha.secret');
            } else {
                $secret = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
            }
        }
        if (!$remote_ip) {
            // Default to the IP address of this client
            // TODO This might cause issues with reverse proxies, look into that later
            $remote_ip = $_SERVER['REMOTE_ADDR'];
        }
        // Check with Google if captcha passed or not
        $post = Post::new('https://www.google.com/recaptcha/api/siteverify')
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
            Log::success(tr('Passed ReCaptcha test'));
        } else {
            Log::warning(tr('Failed ReCaptcha test'));
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
            $key = Config::getString('security.web.captcha.recaptcha.key');
        } else {
            $key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
        }

        return Script::new()
                     ->setAsync(true)
                     ->setDefer(true)
                     ->setSrc($this->script)
                     ->render() . '<div class="g-recaptcha" data-size="' . $this->size . '" data-sitekey="' . $key . '"></div>';
    }
}
