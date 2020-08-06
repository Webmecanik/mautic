<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendEmailFieldsBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Translation\TranslatorInterface;

class ExtendeEmailFieldsIntegrationHelper
{
    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ExtendEmailFieldsModel constructor.
     */
    public function __construct(IntegrationHelper $integrationHelper, TranslatorInterface $translator)
    {
        $this->integrationHelper = $integrationHelper;
        $this->translator        = $translator;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $integration = $this->integrationHelper->getIntegrationObject('ExtendEmailFields');

        return false !== $integration && $integration->getIntegrationSettings()->getIsPublished();
    }

    /**
     * @param $label
     *
     * @return string
     */
    public function getLabel($label)
    {
        if ($this->isActive()) {
            $integration = $this->integrationHelper->getIntegrationObject('ExtendEmailFields');
            $settings    = $integration->mergeConfigToFeatureSettings();
            if (!empty($settings[$label])) {
                return $settings[$label];
            }
        }

        return $this->translator->trans('mautic.extend.email.fields.'.$label);
    }
}
