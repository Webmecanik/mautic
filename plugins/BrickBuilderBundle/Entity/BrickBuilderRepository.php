<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class BrickBuilderRepository extends CommonRepository
{
    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'gjb';
    }
}
