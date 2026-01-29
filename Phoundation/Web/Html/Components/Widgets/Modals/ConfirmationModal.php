<?php

/**
 * Class ConfirmationModal
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Modals;

use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Enums\EnumButtonType;

class ConfirmationModal extends Modal
{
    /**
     * ConfirmationModal class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTitle(tr('Are you sure?'))
             ->addButton(Button::new()
                               ->setButtonType(EnumButtonType::button)
                               ->addClass('btn-close')
                               ->addData('modal', 'mdb-dismiss')
                               ->setOutlined(true)
                               ->setContent(tr('Close')));
    }
}
