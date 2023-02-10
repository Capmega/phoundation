<?php

namespace Phoundation\Filesystem\Traits;



/**
 * Trait Restrictions
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
trait Restrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var \Phoundation\Filesystem\Restrictions $restrictions
     */
    protected \Phoundation\Filesystem\Restrictions $restrictions;



    /**
     * Returns the server restrictions
     *
     * @return \Phoundation\Filesystem\Restrictions
     */
    public function getRestrictions(): \Phoundation\Filesystem\Restrictions
    {
        return $this->restrictions;
    }



    /**
     * Returns the server restrictions
     *
     * @param \Phoundation\Filesystem\Restrictions $restrictions
     * @return static
     */
    public function setRestrictions(\Phoundation\Filesystem\Restrictions $restrictions): static
    {
        $this->restrictions = $restrictions;
        return $this;
    }
}