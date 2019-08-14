<?php

final class Swiftype_Swiftype_Model_System_Config_Source_Catalog_Search_Engine
{
    final public function toOptionArray()
    {
        if (class_exists('Enterprise_Search_Model_Adminhtml_System_Config_Source_Engine')) {
            $sourceModel = new Enterprise_Search_Model_Adminhtml_System_Config_Source_Engine();
            
            $optionArray = $sourceModel->toOptionArray();            
            $optionArray[] = array(
                'value' => Swiftype_Swiftype_Helper_Data::CATALOG_SEARCH_ENGINE_SWIFTYPE,
                'label' => 'Swiftype'
            );
        } else {
            $optionArray = array(
                array(
                    'value' => Swiftype_Swiftype_Helper_Data::CATALOG_SEARCH_ENGINE_SWIFTYPE,
                    'label' => 'Swiftype'
                ),
                array(
                    'value' => Swiftype_Swiftype_Helper_Data::CATALOG_SEARCH_ENGINE_DEFAULT,
                    'label' => 'MySQL'
                )
            );
        }
        
        return $optionArray;
    }
}