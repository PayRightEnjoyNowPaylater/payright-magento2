<?php

namespace Payright\Payright\Model\Config\Source;

class Displayterm implements \Magento\Framework\Option\ArrayInterface {


    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */

    public function toOptionArray() {
        return [
            'Weekly' => 'Weekly',
            'Fortnightly' => 'Fortnightly',
        ];
    }

    public function aftertoOptionArray($OptionsArray)
    {

    }
}