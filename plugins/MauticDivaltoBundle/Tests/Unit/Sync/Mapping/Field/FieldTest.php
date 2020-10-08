<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Sync\Mapping\Field;

use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\Field;

class FieldTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $fieldArray = [
            'name'      => 'email',
            'label'     => 'Email',
            'data_type' => 'bool',
            'required'  => true,
            'writable'  => false,
        ];

        $field = new Field($fieldArray);

        $this->assertEquals($fieldArray['name'], $field->getName());
        $this->assertEquals($fieldArray['label'], $field->getLabel());
        $this->assertEquals($fieldArray['data_type'], $field->getDataType());
        $this->assertTrue($field->isRequired());
        $this->assertFalse($field->isWritable());
    }
}
