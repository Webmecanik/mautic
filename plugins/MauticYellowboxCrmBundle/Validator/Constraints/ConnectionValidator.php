<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Validator\Constraints;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Connection;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConnectionValidator extends ConstraintValidator
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Connection $connection, TranslatorInterface $translator)
    {
        $this->connection = $connection;
        $this->translator = $translator;
    }

    /**
     * Check if connection credentials are valid.
     *
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        /** @var Integration */
        $integration = $this->context->getRoot()->getData();

        $formData = $integration->getApiKeys();

        $url      = $formData['url'];
        $username = $formData['username'];
        $password = $formData['password'];

        $this->connection = clone $this->connection; // Do not leave testing credentials inside used Connection class
        $connected        = $this->connection->test($url, $username, $password);

        if (!$connected) {
            $this->context->buildViolation($this->translator->trans($constraint->message))
                ->addViolation();
        }
    }
}
