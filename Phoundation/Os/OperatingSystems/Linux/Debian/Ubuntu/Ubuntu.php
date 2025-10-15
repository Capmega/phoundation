<?php

namespace Phoundation\Os\OperatingSystems\Linux\Debian\Ubuntu;

class Ubuntu
{
    /**
     * Disables snap package management from Ubuntu installations
     *
     * @see https://askubuntu.com/questions/1399383/how-to-install-firefox-as-a-traditional-deb-package-without-snap-in-ubuntu-22
     * @return static
     */
    public function disableSnap(): static
    {
        // sudo add-apt-repository ppa:mozillateam/ppa
        // See https://askubuntu.com/questions/1399383/how-to-install-firefox-as-a-traditional-deb-package-without-snap-in-ubuntu-22 for the rest
        return $this;
    }


    /**
     * Enables snap package management from Ubuntu installations
     *
     * @see https://askubuntu.com/questions/1399383/how-to-install-firefox-as-a-traditional-deb-package-without-snap-in-ubuntu-22
     * @return static
     */
    public function enableSnap(): static
    {
        // sudo add-apt-repository ppa:mozillateam/ppa
        //
        return $this;
    }
}
