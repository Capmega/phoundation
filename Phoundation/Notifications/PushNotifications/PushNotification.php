<?php

/**
 * Class PushNotification
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Notifications
 */


declare(strict_types=1);

namespace Phoundation\Notifications\PushNotifications;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\PushNotifications\Interfaces\PushNotificationInterface;
use Phoundation\Utils\Config;
use Serhiy\Pushover\Recipient;


// TODO Right now for simplicities sake, this class will just extend PushOver, which extends Serhiy\Pushover\Application
// TODO directly. This needs to be updated that it hides Serhiy\Pushover\Application away and passes the data on

class PushNotification extends PushOver implements PushNotificationInterface
{
    /**
     * The push driver used
     *
     * @var string|null $driver
     */
    protected ?string $driver;

    /**
     * The Push message interface
     *
     * @var PushNotificationInterface $instance
     */
    protected PushNotificationInterface $instance;

    /**
     * Contains the driver classes
     *
     * @var array $drivers
     */
    protected static array $drivers = [
        'pushover' => PushOver::class
    ];


    /**
     * Push class constructor
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Returns a new PushNotification object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Returns the driver to use to send push notifications. Currently only "pushover" is supported
     *
     * @return string|null
     */
    public function getDriver(): ?string
    {
        return $this->driver;
    }


    /**
     * Sets the driver to use to send push notifications. Currently only "pushover" is supported
     *
     * @param string|null $driver
     * @param string|null $token
     *
     * @return static
     */
    public function setDriver(?string $driver, ?string $token): static
    {
        $driver = $driver ?? Config::getString('notifications.push.drivers.default', 'pushover');

        switch ($driver) {
            case 'pushover':
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown or unsupported push driver ":driver" specified', [
                    ':driver' => $driver
                ]));
        }

        $this->driver   = $driver;
// TODO When PushOver application is properly encapsulated, use this specified instance
//        $this->instance = new static::$drivers[$driver]($token);

        return $this;
    }


    /**
     * Sends the actual notification
     *
     * @param string $receiver_token
     *
     * @return static
     */
    public function push(string $receiver_token): static
    {
        $recipient = new Recipient($receiver_token);
    }
}
