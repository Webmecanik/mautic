<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\AutomationBundle\DependencyInjection\Compiler;

use MauticPlugin\AutomationBundle\Security\Provider\KeycloakProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KeycloakPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has('knpu.oauth2.provider.keycloak')) {
            $definition = $container->getDefinition('knpu.oauth2.provider.keycloak');
            $definition->setClass(KeycloakProvider::class);
            $definition->replaceArgument(0, KeycloakProvider::class);
        }
    }
}
