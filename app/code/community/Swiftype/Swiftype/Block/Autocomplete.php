<?php

class Swiftype_Swiftype_Block_Autocomplete
    extends Mage_CatalogSearch_Block_Autocomplete
{
    final public function getSuggestData()
    {
        $helper = Mage::helper('swiftype');
        /* @var $helper Swiftype_Swiftype_Helper_Data */
        
        if ($helper->getUseSwiftype()) {
            $counter = 0;
            $data = array();
            
            foreach ($helper->getSuggestedProducts() as $product) {
                /* @var $product Mage_Catalog_Model_Product */
                $data[] = array(
                    'title' => $product->getName(),
                    'row_class' => (++$counter)%2?'odd':'even',
                    'num_of_results' => ''
                );
            } 
            
            return $data;
        } else {
            return parent::getSuggestData();
        }             
    }
}