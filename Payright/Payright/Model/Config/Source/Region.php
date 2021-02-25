<?php

namespace Payright\Payright\Model\Config\Source;

class Region implements \Magento\Framework\Option\ArrayInterface {


    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */

    public function toOptionArray() {
        return [
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
        ];
    }


}