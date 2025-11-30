<?php

namespace Phoundation\Os\OperatingSystems\Linux\Debian\Ubuntu;

use Phoundation\Os\Processes\Commands\SystemCtl;


class Kubuntu extends Ubuntu
{
    /**
     * Restarts the Kubuntu KDE services required to re
     *
     * @return static
     */
    public function restartSoundServices(): static
    {
        // Restart audio
        //systemctl --user restart pipewire pipewire-pulse wireplumber

        SystemCtl::new()->setService('pipewire')->restart();
        SystemCtl::new()->setService('pipewire-pulse')->restart();
        SystemCtl::new()->setService('wireplumber')->restart();
        return $this;
    }
}
