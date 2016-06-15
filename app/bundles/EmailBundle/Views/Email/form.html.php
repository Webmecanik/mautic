<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');

$dynamicContentPrototype = $form['dynamicContent']->vars['prototype'];
$filterBlockPrototype    = $form['dynamicContent']->children[0]['filters']->vars['prototype'];
$filterSelectPrototype   = $form['dynamicContent']->children[0]['filters']->children[0]['filters']->vars['prototype'];

$variantParent = $email->getVariantParent();
$isExisting    = $email->getId();

$isExisting = $email->getId();

$subheader = ($variantParent) ? '<div><span class="small">'.$view['translator']->trans('mautic.core.variant_of', [
    '%name%'   => $email->getName(),
    '%parent%' => $variantParent->getName(),
]).'</span></div>' : '';

$header = $isExisting ?
    $view['translator']->trans('mautic.email.header.edit',
        ['%name%' => $email->getName()]) :
    $view['translator']->trans('mautic.email.header.new');

$view['slots']->set('headerTitle', $header.$subheader);

$emailType = $form['emailType']->vars['data'];

if (!isset($attachmentSize)) {
    $attachmentSize = 0;
}

$templates = [
    'select'    => 'select-template',
    'countries' => 'country-template',
    'regions'   => 'region-template',
    'timezones' => 'timezone-template',
    'stages'    => 'stage-template',
    'locales'   => 'locale-template',
];

$attr                               = $form->vars['attr'];
$attr['data-submit-callback-async'] = 'clearThemeHtmlBeforeSave';

?>

<?php echo $view['form']->start($form, ['attr' => $attr]); ?>
<div class="box-layout">
    <div class="col-md-9 height-auto bg-white">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li <?php echo !$isExisting ? "class='active'" : ''; ?>><a href="#email-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.form.theme'); ?></a></li>
                    <li <?php echo $isExisting ? "class='active'" : ''; ?>><a href="#source-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.content'); ?></a></li>
                    <li><a href="#advanced-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.advanced'); ?></a></li>
                    <li><a href="#dynamic-content-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.dynamicContent'); ?></a></li>
                </ul>
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade <?php echo !$isExisting ? 'in active' : ''; ?> bdr-w-0" id="email-container">
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['template']); ?>
                            </div>
                        </div>
                        <?php echo $view->render('MauticCoreBundle:Helper:theme_select.html.php', [
                            'type'   => 'email',
                            'themes' => $themes,
                            'active' => $form['template']->vars['value'],
                        ]); ?>
                    </div>

                    <div class="tab-pane fade <?php echo $isExisting ? 'in active' : ''; ?> bdr-w-0" id="source-container">
                        <div class="row">
                          <div class="col-md-12">
                              <?php echo $view['form']->row($form['subject']); ?>
                          </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12" id="customHtmlContainer" style="min-height: 325px;">
                                <?php echo $view['form']->row($form['customHtml']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="pull-left">
                                    <?php echo $view['form']->label($form['plainText']); ?>
                                </div>
                                <div class="text-right pr-10">
                                    <i class="fa fa-spinner fa-spin ml-2 plaintext-spinner hide"></i>
                                    <a class="small" onclick="Mautic.autoGeneratePlaintext();"><?php echo $view['translator']->trans('mautic.email.plaintext.generate'); ?></a>
                                </div>
                                <div class="clearfix"></div>
                                <?php echo $view['form']->widget($form['plainText']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade bdr-w-0" id="advanced-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['fromName']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['fromAddress']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['replyToAddress']); ?>
                            </div>

                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['bccAddress']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="pull-left">
                                    <?php echo $view['form']->label($form['assetAttachments']); ?>
                                </div>
                                <div class="text-right pr-10">
                                    <span class="label label-info" id="attachment-size"><?php echo $attachmentSize; ?></span>
                                </div>
                                <div class="clearfix"></div>
                                <?php echo $view['form']->widget($form['assetAttachments']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade bdr-w-0" id="dynamic-content-container">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                <?php
                                $tabHtml = '<div class="col-xs-3 dynamicContentFilterContainer">';
                                $tabHtml .= '<ul class="nav nav-tabs pr-md pl-md tabs-left" id="dynamicContentTabs">';
                                $tabHtml .= '<li><a href="javascript:void(0);" role="tab" class="btn btn-primary" id="addNewDynamicContent"><i class="fa fa-plus text-success"></i> '.$view['translator']->trans('mautic.core.form.new').'</a></li>';
                                $tabContentHtml = '<div class="tab-content pa-md col-xs-9" id="dynamicContentContainer">';

                                foreach ($form['dynamicContent'] as $i => $dynamicContent) {
                                    $linkText = $dynamicContent['tokenName']->vars['value'] ?: $view['translator']->trans('mautic.core.dynamicContent').' '.($i + 1);

                                    $tabHtml .= '<li class="'.($i === 0 ? ' active' : '').'"><a role="tab" data-toggle="tab" href="#'.$dynamicContent->vars['id'].'">'.$linkText.'</a></li>';

                                    $tabContentHtml .= $view['form']->widget($dynamicContent);
                                }

                                $tabHtml .= '</ul></div>';
                                $tabContentHtml .= '</div>';

                                echo $tabHtml;
                                echo $tabContentHtml;
                                ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['name']); ?>
            <?php if ($isVariant): ?>
                <?php echo $view['form']->row($form['variantSettings']); ?>
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
            <?php else: ?>
            <div id="leadList"<?php echo ($emailType === 'list' || $emailType === 'feed') ? '' : ' class="hide"'; ?>>
                <?php echo $view['form']->row($form['lists']); ?>
            </div>
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['language']); ?>
            <div id="segmentTranslationParent"<?php echo ($emailType == 'template') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['segmentTranslationParent']); ?>
            </div>
            <div id="templateTranslationParent"<?php echo ($emailType == 'list') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['templateTranslationParent']); ?>
            </div>
            <?php endif; ?>

            <?php if (!$isVariant): ?>
            <div id="publishStatus"<?php echo ($emailType === 'template' || $emailType === 'feed' || is_null($emailType)) ? '' : ' class="hide"'; ?>>
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
            <?php endif; ?>

            <?php echo $view['form']->row($form['unsubscribeForm']); ?>

            <!-- For feed -->
            <div id="feedInputs"<?php echo ($emailType === 'feed') ? '' : ' class="hide"'; ?>>
                <hr/>
                <?php echo $view['form']->row($form['feed']); ?>
                <?php echo $view['form']->row($form['nextShoot']); ?>

                <style>
                    #recurency_days_of_week:checked + label + br + div.row,
                    #recurency_days_of_week:checked + label + br + div.row + div.row{
                        display: none;
                    }
                    #recurency_days_of_week:not(:checked) + label + br + div.row + div.row + div.row{
                        display: none;
                    }
                    #recurency_days_of_week + label + br + div.row{          width: 50%;}
                    #recurency_days_of_week + label + br + div.row + div.row{width: 50%;}
                    #recurency_days_of_week + label + br + div.row,
                    #recurency_days_of_week + label + br + div.row + div.row{
                        float: left;
                    }
    			</style>
                <label><?php echo $view['translator']->trans('mautic.core.periodicity.form.recurrence') ?> :</label>
                <br/><input type="radio" value="interval" name="recurency" id="recurency_interval"<?php if(!empty($form['interval']->vars['value'])){?> checked="checked"<?php } ?>/>
                <label for="recurency_interval"><?php echo $view['translator']->trans('mautic.core.periodicity.form.interval') ?></label>
                <br/><input type="radio" value="days_of_week" name="recurency" id="recurency_days_of_week"<?php if(empty($form['interval']->vars['value'])){?> checked="checked"<?php } ?>/>
                <label for="recurency_days_of_week"><?php echo $view['translator']->trans('mautic.core.periodicity.form.days_of_week') ?></label>
                <br/><?php echo $view['form']->row($form['interval']); ?>
                <?php echo $view['form']->row($form['intervalUnit']); ?>
                <?php echo $view['form']->row($form['DaysOfWeek']); ?>
            </div>
        </div>
        <div class="hide">
            <?php echo $view['form']->rest($form); ?>
        </div>
    </div>
</div>

<?php echo $view['form']->end($form); ?>

<div id="dynamicContentPrototype" data-prototype="<?php echo $view->escape($view['form']->widget($dynamicContentPrototype)); ?>"></div>
<div id="filterBlockPrototype" data-prototype="<?php echo $view->escape($view['form']->widget($filterBlockPrototype)); ?>"></div>
<div id="filterSelectPrototype" data-prototype="<?php echo $view->escape($view['form']->widget($filterSelectPrototype)); ?>"></div>

<div class="hide" id="templates">
    <?php foreach ($templates as $dataKey => $template): ?>
        <?php $attr = ($dataKey == 'tags') ? ' data-placeholder="'.$view['translator']->trans('mautic.lead.tags.select_or_create').'" data-no-results-text="'.$view['translator']->trans('mautic.lead.tags.enter_to_create').'" data-allow-add="true" onchange="Mautic.createLeadTag(this)"' : ''; ?>
        <select class="form-control not-chosen <?php echo $template; ?>" name="emailform[dynamicContent][__dynamicContentIndex__][filters][__dynamicContentFilterIndex__][filters][__name__][filter]" id="emailform_dynamicContent___dynamicContentIndex___filters___dynamicContentFilterIndex___filters___name___filter"<?php echo $attr; ?>>
            <?php
            if (isset($form->vars[$dataKey])):
                foreach ($form->vars[$dataKey] as $value => $label):
                    if (is_array($label)):
                        echo "<optgroup label=\"$value\">\n";
                        foreach ($label as $optionValue => $optionLabel):
                            echo "<option value=\"$optionValue\">$optionLabel</option>\n";
                        endforeach;
                        echo "</optgroup>\n";
                    else:
                        if ($dataKey == 'lists' && (isset($currentListId) && (int) $value === (int) $currentListId)) {
                            continue;
                        }
                        echo "<option value=\"$value\">$label</option>\n";
                    endif;
                endforeach;
            endif;
            ?>
        </select>
    <?php endforeach; ?>
</div>

<?php echo $view->render('MauticCoreBundle:Helper:builder.html.php', [
    'type'          => 'email',
    'sectionForm'   => $sectionForm,
    'builderAssets' => $builderAssets,
    'slots'         => $slots,
    'objectId'      => $email->getSessionId(),
]); ?>

<?php
$type = $email->getEmailType();
if (empty($type) || !empty($forceTypeSelection)):
    echo $view->render('MauticCoreBundle:Helper:form_selecttype.html.php',
        [
            'item'               => $email,
            'mauticLang'         => [
                'newListEmail'      => 'mautic.email.type.list.header',
                'newTemplateEmail'  => 'mautic.email.type.template.header',
                'newRssEmail'       => 'mautic.email.type.feed.header'
            ],
            'typePrefix'           => 'email',
            'cancelUrl'            => 'mautic_email_index',
            'header'               => 'mautic.email.type.header',
            'types'                => [
                [
                    'header'       => 'mautic.email.type.template.header',
                    'iconClass'    => 'fa-cube',
                    'description'  => 'mautic.email.type.template.description',
                    'onClick'      => "Mautic.selectEmailType('template');",
                    'color'        => 'success'
                ],
                [
                    'header'       => 'mautic.email.type.list.header',
                    'iconClass'    => 'fa-pie-chart',
                    'description'  => 'mautic.email.type.list.description',
                    'onClick'      => "Mautic.selectEmailType('list');",
                    'color'        => 'primary'
                ],
                [
                    'header'       => 'mautic.email.type.feed.header',
                    'iconClass'    => 'fa-rss-square',
                    'description'  => 'mautic.email.type.feed.description',
                    'onClick'      => "Mautic.selectEmailType('feed');",
                    'color'        => 'warning'
                ]
            ]
        ]);
endif;
