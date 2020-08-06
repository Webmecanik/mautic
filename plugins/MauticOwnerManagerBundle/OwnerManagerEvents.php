<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle;

/**
 * Class OwnerManager.
 *
 * Events available for OwnerManager
 */
final class OwnerManagerEvents
{
    /**
     * The mautic.ownermanager_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerEvent instance.
     *
     * @var string
     */
    const OWNER_MANAGER_PRE_SAVE = 'mautic.ownermanager_pre_save';

    /**
     * The mautic.ownermanager_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerEvent instance.
     *
     * @var string
     */
    const OWNER_MANAGER_POST_SAVE = 'mautic.ownermanager_post_save';

    /**
     * The mautic.ownermanager_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerEvent instance.
     *
     * @var string
     */
    const OWNER_MANAGER_PRE_DELETE = 'mautic.ownermanager_pre_delete';

    /**
     * The mautic.ownermanager_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerEvent instance.
     *
     * @var string
     */
    const OWNER_MANAGER_POST_DELETE = 'mautic.ownermanager_post_delete';

    /**
     * The mautic.ownermanager_on_build event is thrown before displaying the Owner Manager builder form to allow adding of custom actions.
     *
     * The event listener receives a MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerBuilderEvent instance.
     *
     * @var string
     */
    const OWNERMANAGER_ON_BUILD = 'mautic.ownermanager_on_build';

    /**
     * The mautic.ownermanager_on_action event is thrown to execute a action.
     *
     * The event listener receives a MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerActionEvent instance.
     *
     * @var string
     */
    const OWNERMANAGER_ON_ACTION = 'mautic.ownermanager_on_action';

    /**
     * The mautic.ownermanager_on_action_execute event is thrown to execute a owner manager action.
     *
     * The event listener receives a MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerChangeActionExecutedEvent instance.
     *
     * @var string
     */
    const OWNERMANAGER_ON_ACTION_EXECUTE = 'mautic.ownermanager_on_action_execute';
}
