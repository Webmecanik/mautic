<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\AutoSave;

class AutoSaveEmail extends AutoSaveAbstract
{
    const OBJECT_TYPE = 'email';

    protected function getType()
    {
        return self::OBJECT_TYPE;
    }
}
