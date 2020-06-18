<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\LeadBundle\Entity\Lead;

class RedirectEvent extends CommonEvent
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var array
     */
    private $clickthrough;

    /**
     * RedirectEvent constructor.
     *
     * @param string    $url
     * @param Lead|null $lead
     * @param string    $ct
     */
    public function __construct($url, Lead $lead = null, $ct = '')
    {
        $this->url          = $url;
        $this->lead         = $lead;
        $this->clickthrough = ClickthroughHelper::decodeArrayFromUrl($ct);
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return array
     */
    public function getClickthrough()
    {
        return $this->clickthrough;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}
