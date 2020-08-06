<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\AutomationBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\AutomationBundle\DependencyInjection\Compiler\KeycloakPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class AutomationBundle.
 */
class AutomationBundle extends PluginBundleBase
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $container->addCompilerPass(new KeycloakPass());
    }
}
