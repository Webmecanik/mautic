<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\BrickBuilderBundle\Entity\BrickBuilder;
use MauticPlugin\BrickBuilderBundle\Helper\MjmlHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetsSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @var MjmlHelper
     */
    private $mjmlHelper;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var string|null
     */
    private $version;

    /**
     * AssetsSubscriber constructor.
     */
    public function __construct(EmailModel $emailModel, MjmlHelper $mjmlHelper, CoreParametersHelper $coreParametersHelper, UserHelper $userHelper, PathsHelper $pathsHelper)
    {
        $this->emailModel           = $emailModel;
        $this->mjmlHelper           = $mjmlHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->userHelper           = $userHelper;
        $this->pathsHelper          = $pathsHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    private function getActualVersion()
    {
        if (is_null($this->version)) {
            $parameters = include __DIR__.'/../Config/config.php';
            if (is_array($parameters) && isset($parameters['version'])) {
                $this->version = $parameters['version'];
            } else {
                $this->version = '';
            }
        }

        return $this->version;
    }

    public function injectAssets(CustomAssetsEvent $assetsEvent)
    {
        $assetsEvent->addScriptDeclaration($this->addThemesToHide());

        $assetsEvent->addScript('plugins/BrickBuilderBundle/Assets/js/library/init.js?t='.$this->getActualVersion());

        if (!$this->coreParametersHelper->get(BrickBuilder::BRICK_BUILDER_ENABLE)) {
            return;
        }

        $assetsEvent->addScript('plugins/BrickBuilderBundle/Assets/js/library/main.js?t='.$this->getActualVersion());
        $assetsEvent->addScript('plugins/BrickBuilderBundle/Assets/js/library/builder.js?t='.$this->getActualVersion());
        $assetsEvent->addStylesheet('plugins/BrickBuilderBundle/Assets/css/library/main.css?t='.$this->getActualVersion());
        $assetsEvent->addStylesheet('plugins/BrickBuilderBundle/Assets/css/library/builder.css?t='.$this->getActualVersion());
        $assetsEvent->addScriptDeclaration(
            'var emailTokens = '.json_encode($this->getEmailTokens()).';
                var brickBuilderLanguage = "'.$this->getLocale().'";
                var brickBuilderLanguagePath = "'.
            $this->coreParametersHelper->get('site_url').'/plugins/BrickBuilderBundle/Assets/locales/";'
        );

        $assetsEvent->addStyleDeclaration($this->adThemesToHideCSS());
    }

    private function getLocale()
    {
        $locale = $this->coreParametersHelper->get('locale');

        $user = $this->userHelper->getUser();
        if ($user) {
            if ($user->getLocale()) {
                $locale = $user->getLocale();
            }
        }

        if ('fr' == $locale) {
            return 'fr-FR';
        }

        return 'en';
    }

    private function getEmailTokens()
    {
        $tokens   = $this->emailModel->getBuilderComponents(null, ['tokens'], null, false);
        $response = [];
        foreach ($tokens['tokens'] as $token=>$label) {
            $response[] = [
                'key'   => $token,
                'value' => $token,
                'label' => $label,
            ];
        }

        return $response;
    }

    private function addThemesToHide()
    {
        return 'var themes_to_hide = '.json_encode(array_keys($this->mjmlHelper->getHideTemplates())).';';
    }

    private function adThemesToHideCSS()
    {
        return '#email-container .theme-list {display:none}';
    }
}
