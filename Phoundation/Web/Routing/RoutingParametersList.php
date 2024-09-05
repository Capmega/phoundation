<?php

/**
 * Class RouteParametersList
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Exception;
use Phoundation\Core\Log\Log;
use Phoundation\Utils\Strings;
use Phoundation\Web\Exception\RouteException;
use Phoundation\Web\Routing\Interfaces\RoutingParametersInterface;
use Stringable;


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
     * @param RoutingParametersInterface $parameters
     *
     * @return static
     */
    public function add(RoutingParametersInterface $parameters): static
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
     *
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
     * @param Stringable|string $uri
     * @param bool              $system
     *
     * @return RoutingParameters
     */
    public function select(Stringable|string $uri, bool $system = false): RoutingParameters
    {
        if (!$this->ordered) {
            $this->order();
        }

        $uri     = (string) $uri;
        $pattern = null;

        // Search in the system or normal pages list for the parameters
        foreach (($system ? $this->system_list : $this->list) as $pattern => $parameters) {
            if (!$pattern) {
                // This is the last, default parameters object. Use this.
                break;
            }

            try {
                if (!preg_match_all($pattern, $uri, $matches)) {
                    continue;
                }

            } catch (Exception $e) {
                throw RouteException::new(tr('Routing regular expression pattern ":regex" failed with error ":e"', [
                    ':e'     => $e->getMessage(),
                    ':regex' => $pattern,
                ]), $e)->addData(['failed_pattern' => $pattern]);
            }

            $parameters->setMatches($matches)->setUri($uri);

            // Use this template
            Log::success(tr('Selected routing parameters pattern ":pattern" with template ":template" and directory ":directory" for:system page from URI ":uri"', [
                ':system'    => ($system ? ' system' : ''),
                ':uri'       => $uri,
                ':directory' => Strings::from($parameters->getRootDirectory(), DIRECTORY_ROOT),
                ':template'  => $parameters->getTemplate(),
                ':pattern'   => $pattern,
            ]), 4);

            return $parameters;
        }
        if (!isset($parameters)) {
            throw new RouteException(tr('Cannot find routing parameters for target ":target", no routing parameters available', [
                ':target' => $uri,
            ]));
        }

        // Use default template
        $parameters->setUri($uri);

        Log::action(tr('Using default parameters ":pattern" with template ":template" and directory ":directory" for:system page from URI ":uri"', [
            ':system'    => ($system ? ' system' : ''),
            ':uri'       => $uri,
            ':directory' => $parameters->getRootDirectory(),
            ':template'  => $parameters->getTemplate(),
            ':pattern'   => $pattern,
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
