<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Html;

/**
 * Trait TraitDataProcess
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataProcessName
{
    /**
     * The process_name for this object
     *
     * @var string|null $process_name
     */
    protected ?string $process_name = null;


    /**
     * Returns the process name
     *
     * @return string|null
     */
    public function getProcessName(): ?string
    {
        return $this->process_name;
    }


    /**
     * Sets the process name
     *
     * @param string|null $process_name
     * @param bool        $make_safe
     *
     * @return static
     */
    public function setProcessName(?string $process_name, bool $make_safe = true): static
    {
        if ($make_safe) {
            $this->process_name = Html::safe($process_name);

        } else {
            $this->process_name = $process_name;
        }

        return $this;
    }
}