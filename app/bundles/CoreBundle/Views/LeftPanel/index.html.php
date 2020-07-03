<?php

/*
 * @copyright 2014 Mautic Contributors. All rights reserved
* @author Mautic
* @link http://mautic.org
* @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
$extraMenu              = $view['menu']->render('extra');
$customSidebarStyle     = $view['core_parameters']->getParameter('custom_sidebar_style');
$customSidebarLinkStyle = $view['core_parameters']->getParameter('custom_sidebar_link_style');
$customLogoSrc          = $view['core_parameters']->getParameter('custom_logo_src');
$customLogoStyle        = $view['core_parameters']->getParameter('custom_logo_style');
$customLogoTextSrc      = $view['core_parameters']->getParameter('custom_logo_text_src');
$customLogoTextStyle    = $view['core_parameters']->getParameter('custom_logo_text_style');
$customMenuStyle        = $view['core_parameters']->getParameter('custom_menu_style');

$customLogoStyleAttr = '';
if (!empty($customLogoStyle)) {
    $customLogoStyleAttr = ' style="'.$customLogoStyle.';"';
}

$customLogoTextStyleAttr = '';
if (!empty($customLogoTextStyle)) {
    $customLogoTextStyleAttr = ' style="'.$customLogoTextStyle.';"';
}

$customMenuStyleAttr = '';
if (!empty($customMenuStyle)) {
    $customMenuStyleAttr = ' style="'.$customMenuStyle.';"';
}

?>

<!-- start: sidebar-header -->
<div class="sidebar-header"
	<?php

echo (!empty($customSidebarStyle)) ? ' style="'.$customSidebarStyle.'"' : '';
?>>
	<!-- brand -->
	<a class="mautic-brand<?php

echo (!empty($extraMenu)) ? ' pull-left pl-0 pr-0' : '';
?>" href="#" <?php

echo (!empty($customSidebarLinkStyle)) ? ' style="'.$customSidebarLinkStyle.'"' : '';
?>>
		<?php

echo '<img src="'.$view['assets']->getUrl($customLogoSrc).'" '.$customLogoStyleAttr.' />';
?>
		<?php

echo '<img src="'.$view['assets']->getUrl($customLogoTextSrc).'" '.$customLogoTextStyleAttr.' />';
?>
	</a>
    <?php
    if (!empty($extraMenu)) :
        ?>
        <div class="dropdown extra-menu">
		<a href="#" data-toggle="dropdown" class="dropdown-toggle"> <i
			class="fa fa-chevron-down fa-lg"></i>
		</a>
            <?php

        echo $extraMenu;
        ?>
        </div>




    <?php endif;
    ?>
    <!--/ brand -->
</div>
<!--/ end: sidebar-header -->

<!-- start: sidebar-content -->
<div class="sidebar-content" <?php

echo $customMenuStyleAttr;
?>>
	<!-- scroll-content -->
	<div class="scroll-content slimscroll">
		<!-- start: navigation -->
		<nav class="nav-sidebar">
            <?php

            echo $view['menu']->render('main');
            ?>

            <!-- start: left nav -->
			<ul class="nav sidebar-left-dark">
				<li class="hidden-xs"><a href="javascript:void(0)"
					data-toggle="minimize" class="sidebar-minimizer"><span
						class="direction icon pull-left fa"></span><span
						class="nav-item-name pull-left text"><?php

    echo $view['translator']->trans('mautic.core.menu.left.collapse');
    ?></span></a>
				</li>
			</ul>
			<!--/ end: left nav -->

		</nav>
		<!--/ end: navigation -->
	</div>
	<!--/ scroll-content -->
</div>
<!--/ end: sidebar-content -->