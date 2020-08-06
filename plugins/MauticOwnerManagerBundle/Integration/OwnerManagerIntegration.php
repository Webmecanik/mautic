<?php

/*
 * @copyright   2019 MTCExtendee. All rights reserved
 * @author      MTCExtendee
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\FormBuilder;

class OwnerManagerIntegration extends AbstractIntegration
{
    const INTEGRATION_NAME = 'OwnerManager';

    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    public function getDisplayName()
    {
        return 'Owner Manager';
    }

    public function getAuthenticationType()
    {
        return 'none';
    }

    public function getRequiredKeyFields()
    {
        return [
        ];
    }

    public function getIcon()
    {
        return 'plugins/MauticOwnerManagerBundle/Assets/img/icon.png';
    }
}
