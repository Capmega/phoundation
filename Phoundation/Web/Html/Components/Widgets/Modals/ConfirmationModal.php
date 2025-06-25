<?php

/**
 * Class ConfirmationModal
 *
 * @author    Sven Olaf Oostenbrink <sven@medinet.ca>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   This plugin is developed by Medinet and may only be used by others with explicit written authorization
 * @copyright Copyright © 2025 Medinet <copyright@medinet.ca>
 * @package   Medinet\Billing
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
