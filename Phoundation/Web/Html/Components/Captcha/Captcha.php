<?php

/**
 * Interface Captcha
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Captcha;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Captcha\Interfaces\CaptchaInterface;
use Phoundation\Web\Html\Components\ElementsBlock;


abstract class Captcha extends ElementsBlock implements CaptchaInterface
{
    /**
     * Returns a new Captcha for the configured provider
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     *
     * @return static
     */
    public static function new(IteratorInterface|array|string|PDOStatement|null $source = null): static
    {
        switch (Config::getString('security.web.captcha.provider', 'recaptcha')) {
            case 'recaptcha':
                return new ReCaptcha2($source);
            case '':
                throw new OutOfBoundsException(tr('No captcha provider specified'));
            default:
                throw new OutOfBoundsException(tr('Unknown captcha provider ":provider" specified', [
                    ':provider' => Config::getString('', 'recaptcha'),
                ]));
        }
    }
}
