<?php

namespace Phoundation\Web\Server\Interfaces;

interface WebserverInterface
{
    /**
     * Disconnect from webserver but let the process continue working
     */
    function disconnect(): void;
}
