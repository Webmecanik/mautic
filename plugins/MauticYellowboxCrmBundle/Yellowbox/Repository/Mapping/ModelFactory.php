<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Mapping;

use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Account;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Contact;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Event;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Lead;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\User;

class ModelFactory
{
    public function createLead(array $data): Lead
    {
        return new Lead($data);
    }

    public function createContact(array $data): Contact
    {
        return new Contact($data);
    }

    public function createAccount(array $data): Account
    {
        return new Account($data);
    }

    public function createEvent(array $data): Event
    {
        return new Event($data);
    }

    public function createUser(array $data): User
    {
        return new User($data);
    }
}
