<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEcrBundle\Sync\DAO;

use DateTimeInterface;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use MauticPlugin\MauticDolistBundle\Exception\InvalidValueException;

class InputDAO
{
    /**
     * @var DateTimeInterface|null
     */
    private $startDateTime;

    /**
     * @var DateTimeInterface|null
     */
    private $endDateTime;

    public function __construct(array $input)
    {
        $this->startDateTime = $this->validateDateTime($input, 'start-datetime');
        $this->endDateTime   = $this->validateDateTime($input, 'end-datetime');
    }

    /**
     * @throws \Exception
     */
    private function validateDateTime(array $input, string $optionName): ?DateTimeInterface
    {
        if (empty($input[$optionName])) {
            return null;
        }

        if ($input[$optionName] instanceof DateTimeInterface) {
            return $input[$optionName];
        } else {
            try {
                return is_string($input[$optionName]) ? (new DateTimeHelper($input[$optionName], 'Y-m-d H:i:s', 'local'))->getDateTime() : null;
            } catch (\Throwable $e) {
                throw new InvalidValueException("'$input[$optionName]' is not valid. Use 'Y-m-d H:i:s' format like '2018-12-24 20:30:00' or something like '-10 minutes'");
            }
        }
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }
}
