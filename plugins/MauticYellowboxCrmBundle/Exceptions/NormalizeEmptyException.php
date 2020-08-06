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

namespace MauticPlugin\MauticYellowboxCrmBundle\Exceptions;

use Exception;

class NormalizeEmptyException extends Exception
{
    /**
     * @var string
     */
    private $value;

    /**
     * NormalizeEmptyException constructor.
     *
     * @param string $message
     * @param string $value
     */
    public function __construct($message = '', $value = '')
    {
        parent::__construct($message);
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
