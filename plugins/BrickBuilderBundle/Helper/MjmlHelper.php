<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use MauticPlugin\BrickBuilderBundle\Entity\BrickBuilder;

class MjmlHelper
{
    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var ThemeHelper
     */
    private $themeHelper;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * MjmlTemplate constructor.
     */
    public function __construct(TemplatingHelper $templatingHelper, ThemeHelper $themeHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->templatingHelper     = $templatingHelper;
        $this->themeHelper          = $themeHelper;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public function getHideTemplates()
    {
        if ($this->coreParametersHelper->get(BrickBuilder::BRICK_BUILDER_ENABLE) && $this->coreParametersHelper->get(BrickBuilder::BOTH_BUILDER_SUPPORT)) {
            return [];
        }

        $themes = $this->themeHelper->getInstalledThemes('email', true);
        foreach ($themes as $key=> $theme) {
            $mjmlExist = file_exists($theme['dir'].'/html/email.mjml.twig');
            if ($this->coreParametersHelper->get(BrickBuilder::BRICK_BUILDER_ENABLE)) {
                if ($mjmlExist) {
                    unset($themes[$key]);
                }
            } else {
                if (!$mjmlExist) {
                    unset($themes[$key]);
                }
            }
        }

        return $themes;
    }

    /**
     * @param $template
     *
     * @return mixed|string|null
     *
     * @throws \Exception
     */
    public function checkForMjmlTemplate($template)
    {
        $parser     = $this->templatingHelper->getTemplateNameParser();
        $templating = $this->templatingHelper->getTemplating();
        $template   = $parser->parse($template);

        $twigTemplate = clone $template;
        $twigTemplate->set('engine', 'twig');

        if ($templating->exists($twigTemplate)) {
            return $twigTemplate->getLogicalName();
        }

        return null;
    }
}
