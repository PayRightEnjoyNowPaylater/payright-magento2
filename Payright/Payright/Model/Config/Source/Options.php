<?php 

namespace Payright\Payright\Model\Config\Source;

class Options implements \Magento\Framework\Option\ArrayInterface
{ 


    /**
     * Return array of options as value-label pairs, eg. value => label
     *
    * @return array
    */
     
    public function toOptionArray()
    {
        return [
            'ModalOptionOne' => 'Option One',
            'ModalOptionsTwo' => 'Option Two',
        ];
    }


 
}

?>