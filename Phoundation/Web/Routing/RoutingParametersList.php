<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Core\Log\Log;
use Phoundation\Web\Exception\RouteException;

/**
 * Class RouteParametersList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class RoutingParametersList
{
    /**
     * If true will order lists before searching for the right parameters
     *
     * @var bool $ordered
     */
    protected bool $ordered = false;

    /**
     * Normal page parameters
     *
     * @var array $system_list
     */
    protected array $list = [];

    /**
     * System page parameters
     *
     * @var array $system_list
     */
    protected array $system_list = [];


    /**
     * Add the specified parameters
     *
     * @param RoutingParameters $parameters
     * @return $this
     */
    public function add(RoutingParameters $parameters): static
    {
        if ($parameters->getSystemPagesOnly()) {
            $this->system_list[$parameters->getPattern()] = $parameters;
        } else {
            $this->list[$parameters->getPattern()] = $parameters;
        }

        $this->ordered = false;
        return $this;
    }


    /**
     * Clears all routing parameters
     *
     * @return static
     */
    public function clear(): static
    {
        $this->system_list = [];
        $this->list        = [];

        return $this;
    }


    /**
     * Returns all normal routing parameters
     *
     * @param bool $system
     * @return array
     */
    public function list(bool $system = false): array
    {
        if ($system) {
            return $this->system_list;
        }

        return $this->list;
    }


    /**
     * Add the specified parameters
     *
     * @param string $uri
     * @param bool $system
     * @return RoutingParameters
     */
    public function select(string $uri, bool $system = false): RoutingParameters
    {
        if (!$this->ordered) {
            $this->order();
        }

        $pattern = null;

        // Search in the system or normal pages list for the parameters
        foreach (($system ? $this->system_list : $this->list) as $pattern => $parameters) {
            if (!$pattern) {
                // This is the last, default parameters object. Use this.
                break;
            }

            if (preg_match_all($pattern, $uri, $matches)) {
                $parameters
                    ->setMatches($matches)
                    ->setUri($uri);

                // Use this template
                Log::action(tr('Selected parameters pattern ":pattern" with template ":template" and path ":path" for:system page from URI ":uri"', [
                    ':system'   => ($system ? ' system' : ''),
                    ':uri'      => $uri,
                    ':path'     => $parameters->getRootPath(),
                    ':template' => $parameters->getTemplate(),
                    ':pattern'  => $pattern
                ]));

                return $parameters;
            }
        }

        if (!isset($parameters)) {
            throw new RouteException(tr('Cannot find routing parameters for target ":target", no routing parameters available', [
                ':target' => $uri
            ]));
        }

        // Use default template
        $parameters->setUri($uri);

        Log::action(tr('Using default parameters ":pattern" with template ":template" and path ":path" for:system page from URI ":uri"', [
            ':system'   => ($system ? ' system' : ''),
            ':uri'      => $uri,
            ':path'     => $parameters->getRootPath(),
            ':template' => $parameters->getTemplate(),
            ':pattern'  => $pattern
        ]));

        return $parameters;
    }


    /**
     * Ensure that the default parameters are at the bottom of the list
     *
     * @return void
     */
    protected function order(): void
    {
        // Order normal page parameters
        if (array_key_exists('', $this->list)) {
            $pattern = isset_get($this->list['']);
            unset($this->list['']);
            $this->list[''] = $pattern;
        }

        // Order system page parameters
        if (array_key_exists('', $this->system_list)) {
            $pattern = isset_get($this->system_list['']);
            unset($this->system_list['']);
            $this->system_list[''] = $pattern;
        }

        $this->ordered = true;
    }
}