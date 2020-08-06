<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:slim.html.php');
$view['slots']->set('pageTitle', 'tester');
$view['slots']->set('headerTitle', 'test');
$view['slots']->set('mauticContent', 'report');
?>

<style>
    #app-content {margin-top:1em;}
</style>

<h2 class="text-center"><?php echo $view['translator']->trans(
        'mautic.contactoverview.headline',
        [
            '%contactId%' => '<a href="'.$view['router']->url(
                    'mautic_contact_action',
                    ['objectAction' => 'view', 'objectId' => $lead->getId()]
                ).'">'.$lead->getId().'</a>',
        ]
    ); ?></h2>

<br>
<div id="timeline-table">
    <?php echo $view->render(
        'MauticContactOverviewBundle:Contact:list.html.php',
        [
            'events' => $events,
            'lead'   => $lead,
            'hash'   => $hash,
        ]
    ); ?>
</div>