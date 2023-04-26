<?php

namespace Phoundation\Web\Http\Html\Components\Captcha;

use Phoundation\Core\Config;
use Phoundation\Data\Traits\DataAction;
use Phoundation\Data\Traits\DataSelector;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Network\Curl\Post;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Page;


/**
 * Class ReCaptcha
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class ReCaptcha extends Captcha
{
    use DataAction;
    use DataSelector;


    /**
     * Returns true if the token is valid for the specified action
     *
     * @param string $token
     * @param string $action
     * @param float $min_score
     * @return bool
     */
    public function tokenIsValid(string $token, string $action, float $min_score = 0.5): bool
    {
        if (!$this->action) {
            throw new OutOfBoundsException(tr('No action specified'));
        }

        if (!$this->selector) {
            throw new OutOfBoundsException(tr('No selector specified'));
        }

        $c = Post::new('https://www.google.com/recaptcha/api/siteverify')
            ->setPostUrlEncoded(true)
            ->addPostValues([
                'secret'   => Config::getString('security.accounts.captcha.recaptcha.secret'),
                'response' => $token])
            ->execute();

        $response = $c->getResultData();
        $response = Json::decode($response);

        if ($response["success"] and ($response["action"] == Page::getRequestMethod()) and ($response["score"] >= $min_score)) {
            return true;
        }

        return false;
    }


    /**
     * Renders and returns the HTML for the google ReCAPTCHA
     *
     * @return string
     */
    public function render(): string
    {
        Page::loadJavascript('https://www.google.com/recaptcha/api.js?render=6LdLk7EUAAAAAEWHuB2tabMmlxQ2-RRTLPHEGe9Y');

        return Script::new()
            ->setContent('$("#newsletterForm").submit(function(event) {
                                    event.preventDefault();
                            
                                    grecaptcha.ready(function() {
                                        grecaptcha.execute("6LdLk7EUAAAAAEWHuB2tabMmlxQ2-RRTLPHEGe9Y", {action: "' . $this->action . '"}).then(function(token) {
                                            $("' . $this->selector . '").prepend("<input type="hidden" name="token" value="" + token + "">");
                                            $("' . $this->selector . '").prepend("<input type="hidden" name="action" value="' . $this->action . '">");
                                            $("' . $this->selector . '").unbind("submit").submit();
                                        });;
                                    });
                              });')
            ->render();
    }
}