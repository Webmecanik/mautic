<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\Normalize;

class NormalizeVariantsToDynamicContents
{
    /**
     * @var array
     */
    private $variants;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var array
     */
    private $filters;

    public function __construct(string $variants, string $fields)
    {
        $this->variants = json_decode($variants, true);
        $this->fields   = json_decode($fields, true);

        $this->normalize();
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    private function normalize()
    {
        foreach ($this->variants as $key=>$variant) {
            foreach ($variant['filters'] as $key2=>$filter) {
                $rules         = json_decode($filter['filters'], true);
                $this->filters = [];
                unset($this->variants[$key]['filters'][$key2]['filters']);
                $this->variants[$key]['filters'][$key2]['filters'] = $this->normalize2($rules['rules']);
            }
        }
    }

    private function normalize2(array $rules, $or = false)
    {
        foreach ($rules as $key=> $rule) {
            if (isset($rule['rules'])) {
                $this->normalize2($rule['rules'], true);
            } else {
                $normalizeFilter = [
                    'field'    => $rule['field'],
                    'operator' => $rule['operator'],
                    'filter'   => $rule['value'],
                    'glue'     => 'and',
                    'type'     => $this->fields[$rule['field']],
                    'object'   => 'lead',
                    'display'  => '',
                ];
                if ($or) {
                    $normalizeFilter['glue'] = 'or';
                    $or                      = false;
                }
                $this->filters[]= $normalizeFilter;
            }
        }

        return $this->filters;
    }
}
