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
use Phoundation\Web\Html\Components\Interfaces\ScriptInterface;
use Phoundation\Web\Html\Components\Interfaces\ScriptsInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Scripts;


trait TraitDataScripts
{
    /**
     * The scripts to use
     *
     * @var ScriptsInterface|null $o_scripts
     */
    protected ?ScriptsInterface $o_scripts = null;


    /**
     * Returns the scripts
     *
     * @return ScriptsInterface|null
     */
    public function getScriptsObject(): ?ScriptsInterface
    {
        if ($this->o_scripts === null) {
            // Auto initialize
            $this->o_scripts = new Scripts();
        }

        return $this->o_scripts;
    }


    /**
     * Returns the scripts
     *
     * @param ScriptInterface|ScriptsInterface $o_scripts
     *
     * @return static
     */
    public function addScriptObject(ScriptInterface|ScriptsInterface $o_scripts): static
    {
        if ($o_scripts instanceof ScriptsInterface) {
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
     * @param ScriptsInterface|null $o_scripts
     *
     * @return static
     */
    public function setScriptsObject(ScriptsInterface|null $o_scripts): static
    {
        $this->o_scripts = $o_scripts;
        return $this;
    }
}
