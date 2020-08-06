<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendEmailFieldsBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticExtendEmailFieldsBundle\Entity\ExtendEmailFields;
use MauticPlugin\MauticRecommenderBundle\Entity\EventRepository;
use MauticPlugin\MauticRecommenderBundle\Entity\RecommenderTemplateRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ExtendEmailFieldsModel extends AbstractCommonModel
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * ExtendEmailFieldsModel constructor.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     *
     * @return EventRepository
     */
    public function getRepository()
    {
        /** @var RecommenderTemplateRepository $repo */
        $repo = $this->em->getRepository('MauticExtendEmailFieldsBundle:ExtendEmailFields');

        $repo->setTranslator($this->translator);

        return $repo;
    }

    /**
     * Add or edit email settings entity based on request.
     */
    public function addOrEditEntity(Email $email)
    {
        $settingsExtend = $this->getRepository()->findOneBy(['email'=>$email]);

        if (!$settingsExtend) {
            $settingsExtend = new ExtendEmailFields();
            $settingsExtend->setEmail($email);
        }

        if ($this->requestStack->getCurrentRequest()->request->has('extra1')) {
            $extra1 = $this->requestStack->getCurrentRequest()->get('extra1', '');
            $settingsExtend->setExtra1($extra1);
        }
        if ($this->requestStack->getCurrentRequest()->request->has('extra2')) {
            $extra2 = $this->requestStack->getCurrentRequest()->get('extra2', '');
            $settingsExtend->setExtra2($extra2);
        }

        $this->getRepository()->saveEntity($settingsExtend);
    }
}
