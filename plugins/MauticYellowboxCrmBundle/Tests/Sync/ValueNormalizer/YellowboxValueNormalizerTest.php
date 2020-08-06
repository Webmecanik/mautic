<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     2.11.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Tests\Sync\ValueNormalizer;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers\MauticYellowboxTransformer;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers\YellowboxMauticTransformer;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\YellowboxValueNormalizer;
use MauticPlugin\MauticYellowboxCrmBundle\Tests\TestDataProvider\ModulesDescriptionProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\LeadFieldDirection;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Type\CommonType;

class YellowboxValueNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var YellowboxValueNormalizer
     */
    private $valueNormalizer;

    /**
     * @var array
     */
    private $normalizationsMautic = [
        'date'          => ['2018-01-02', null],
        'string'        => ['khjmkjhol.sadasd', null],
        'phone'         => ['001777786786', null],
        'email'         => ['jan.kozak@mautic.com', null],
        'picklist'      => ['A', 'B'],
        'multipicklist' => ['A|B', null],
        'url'           => ['http://www.mautic.com', null],
        'currency'      => [12.56, null],
        'integer'       => [34, 66, null],
        'datetime'      => ['2001-09-09 22:22:12', null],
        'text'          => ["lala\nohlala\nnnnn"],
        'boolean'       => [0, 1, null, true, false],
        'double'        => [3.14159265434, null, 0],
        'skype'         => ['jajaja'],
        'time'          => ['12:20', '23:59'],
    ];

    /** @var CommonType[] */
    private $yellowboxTypes;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = new YellowboxValueNormalizer(
            new YellowboxMauticTransformer(),
            new MauticYellowboxTransformer()
        );

        $this->yellowboxTypes = ModulesDescriptionProvider::getLeadFieldTypes();
    }

    public function testNormalizeForYellowbox()
    {
        $typeObject       = new \stdClass();
        $typeObject->name = 'string';

        $objData = [
            'label'     => 'Test',
            'name'      => 'test',
            'nullable'  => true,
            'editable'  => true,
            'type'      => $typeObject,
            'mandatory' => false,
        ];

        $fieldDirection = new LeadFieldDirection();
        $fieldInfo      = new ModuleFieldInfo((object) $objData, $fieldDirection);

        foreach ($this->normalizationsMautic as $type => $item) {
            $fieldInfo->setType($this->yellowboxTypes[$type]);
            $item = is_array($item) ? $item : [$item];
            foreach ($item as $testValue) {
                $originalValue = new NormalizedValueDAO($type, $testValue);
                $fieldDAO      = new FieldDAO('test_field', $originalValue);
                $normalized    = $this->valueNormalizer->normalizeForYellowbox($fieldInfo, $fieldDAO);
                $unnormalized  = $this->valueNormalizer->normalizeForMauticTyped($fieldInfo, $normalized);
                $this->assertEquals($testValue, $unnormalized->getNormalizedValue(),
                    sprintf('Transformation for %s type failed %s<>%s<>%s',
                        $type, $testValue, $normalized, $unnormalized->getNormalizedValue()
                    )
                );
            }
        }
    }
}
