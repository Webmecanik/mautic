<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Helper;

class MatchHelper
{
    /**
     * @param string|null $find
     * @param string      $string
     *
     * @return bool
     */
    public static function matchRegexp($find = null, $string)
    {
        if (!empty($find)) {
            $finds = explode('|', $find);
            foreach ($finds as $find) {
                if (fnmatch($find, $string)) {
                    return true;
                }
            }
        }

        return false;
    }
}
