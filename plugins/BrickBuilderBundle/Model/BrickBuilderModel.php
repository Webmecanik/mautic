<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\Model;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\BrickBuilderBundle\Entity\BrickBuilder;
use MauticPlugin\BrickBuilderBundle\Entity\BrickBuilderRepository;
use MauticPlugin\BrickBuilderBundle\Normalize\NormalizeVariantsToDynamicContents;
use Symfony\Component\HttpFoundation\RequestStack;

class BrickBuilderModel extends AbstractCommonModel
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * BrickBuilderModel constructor.
     */
    public function __construct(RequestStack $requestStack, EmailModel $emailModel)
    {
        $this->requestStack = $requestStack;
        $this->emailModel   = $emailModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return BrickBuilderRepository
     */
    public function getRepository()
    {
        /** @var BrickBuilderRepository $repository */
        $repository = $this->em->getRepository('BrickBuilderBundle:BrickBuilder');

        $repository->setTranslator($this->translator);

        return $repository;
    }

    /**
     * Add or edit email settings entity based on request.
     */
    public function addOrEditEntity(Email $email)
    {
        $brickBuilder = $this->getRepository()->findOneBy(['email' => $email]);

        if (!$brickBuilder) {
            $brickBuilder = new BrickBuilder();
            $brickBuilder->setEmail($email);
        }

        if ($this->requestStack->getCurrentRequest()->request->has('brickbuilder')) {
            $data = $this->requestStack->getCurrentRequest()->get('brickbuilder', '');

            if (isset($data['customMjml'])) {
                $brickBuilder->setCustomMjml($data['customMjml']);
            }
            if (!empty($data['customVariants']) && !empty($data['customFields'])) {
                $normalize = new NormalizeVariantsToDynamicContents($data['customVariants'], $data['customFields']);
                $email->setDynamicContent($normalize->getVariants());
            }
        }

        $this->getRepository()->saveEntity($brickBuilder);

        $customHtml = ArrayHelper::getValue('customHtml', $this->requestStack->getCurrentRequest()->get('emailform'));
        $email->setCustomHtml($customHtml);
        $this->emailModel->getRepository()->saveEntity($email);
    }

    public function getBrickBuilderFromEmailId($emailId)
    {
        if ($email = $this->emailModel->getEntity($emailId)) {
            return $this->getRepository()->findOneBy(['email' => $email]);
        }
    }
}
