<?php

/**
 * Class RedisQueue
 *
 *
 * @author    Harrison Macey <harrison@medinet.ca>
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Redis;

use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;

class RedisQueue extends RedisQueueCore
{
    /**
     * RedisQueue class constructor
     *
     * @param ConnectorInterface $connector
     * @param string             $queue
     */
    public function __construct(ConnectorInterface $connector, string $queue)
    {
        $this->redis = Redis::new($connector);
        $this->redis->setDatabase($connector->getDatabase());
        $this->queue = $queue;
    }


    /**
     * Returns a new RedisQueue object
     *
     * @param ConnectorInterface $connector
     * @param string             $queue
     *
     * @return static
     */
    public static function new(ConnectorInterface $connector, string $queue): static
    {
        return new static($connector, $queue);
    }
}
