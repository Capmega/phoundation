<?php

/**
 * Trait TraitDataScripts
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\Script;


trait TraitDataScripts
{
    /**
     * The scripts to use
     *
     * @var IteratorInterface|null $o_scripts
     */
    protected ?IteratorInterface $o_scripts = null;


    /**
     * Returns the scripts
     *
     * @return IteratorInterface|null
     */
    public function getScriptsObject(): ?IteratorInterface
    {
        if ($this->o_scripts === null) {
            // Auto initialize
            $this->o_scripts = new Iterator();
        }

        return $this->o_scripts;
    }


    /**
     * Returns the scripts
     *
     * @param Script|IteratorInterface $o_scripts
     *
     * @return static
     */
    public function addScriptObject(Script|IteratorInterface $o_scripts): static
    {
        if ($o_scripts instanceof IteratorInterface) {
            $o_scripts = $o_scripts->getSource();
        }

        if (is_array($o_scripts)) {
            foreach ($o_scripts as $o_script) {
                $this->addScriptObject($o_script);
            }

        } else {
            $this->getScriptsObject()->add($o_scripts);
        }

        return $this;
    }


    /**
     * Sets the scripts
     *
     * @param IteratorInterface|null $o_scripts
     *
     * @return static
     */
    public function setScriptsObject(IteratorInterface|null $o_scripts): static
    {
        $this->o_scripts = $o_scripts;
        return $this;
    }
}
