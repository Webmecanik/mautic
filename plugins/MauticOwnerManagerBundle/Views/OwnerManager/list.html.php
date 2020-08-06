<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ('index' == $tmpl) {
    $view->extend('MauticOwnerManagerBundle:OwnerManager:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered point-list" id="pointTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#pointTable',
                        'routeBase'       => 'ownermanager',
                        'templateButtons' => [
                            'delete' => $permissions['ownermanager:ownermanager:delete'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'ownermanager',
                        'orderBy'    => 'p.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-point-name',
                        'default'    => true,
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'ownermanager',
                        'orderBy'    => 'cat.title',
                        'text'       => 'mautic.core.category',
                        'class'      => 'visible-md visible-lg col-point-category',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'ownermanager',
                        'orderBy'    => 'p.owner',
                        'text'       => 'mautic.ownermanager.thead.owner',
                        'class'      => 'visible-md visible-lg col-point-delta',
                    ]
                );

                echo '<th class="col-point-action">'.$view['translator']->trans('mautic.ownermanager.thead.action').'</th>';

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'ownermanager',
                        'orderBy'    => 'p.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-point-id',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => [
                                    'edit'   => $permissions['ownermanager:ownermanager:edit'],
                                    'clone'  => $permissions['ownermanager:ownermanager:create'],
                                    'delete' => $permissions['ownermanager:ownermanager:delete'],
                                ],
                                'routeBase' => 'ownermanager',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <div>

                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                ['item' => $item, 'model' => 'ownermanager']
                            ); ?>
                            <?php if ($permissions['ownermanager:ownermanager:edit']): ?>
                                <a href="<?php echo $view['router']->path(
                                    'mautic_ownermanager_action',
                                    ['objectAction' => 'edit', 'objectId' => $item->getId()]
                                ); ?>" data-toggle="ajax">
                                    <?php echo $item->getName(); ?>
                                </a>
                            <?php else: ?>
                                <?php echo $item->getName(); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $description; ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php $category = $item->getCategory(); ?>
                        <?php $catName  = ($category)
                            ? $category->getTitle()
                            : $view['translator']->trans(
                                'mautic.core.form.uncategorized'
                            ); ?>
                        <?php $color = ($category) ? '#'.$category->getColor() : 'inherit'; ?>
                        <span style="white-space: nowrap;"><span class="label label-default pa-4"
                                                                 style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                    </td>
                    <td class="visible-md visible-lg"><?php echo sprintf(
                            '%s %s',
                            $item->getOwner()->getFirstname(),
                            $item->getOwner()->getLastname()
                        ); ?></td>
                    <?php
                    $type   = $item->getType();
                    $action = (isset($actions[$type])) ? $actions[$type]['label'] : '';
                    ?>
                    <td><?php echo $view['translator']->trans($action); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems' => count($items),
                'page'       => $page,
                'limit'      => $limit,
                'menuLinkId' => 'mautic_ownermanager_index',
                'baseUrl'    => $view['router']->path('mautic_ownermanager_index'),
                'sessionVar' => 'ownermanager',
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:noresults.html.php'
    ); ?>
<?php endif; ?>
