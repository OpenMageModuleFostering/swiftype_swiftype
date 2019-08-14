<?php

final class Swiftype_Swiftype_Model_System_Config_Source_Catalog_Search_Spelling
{
    final public function toOptionArray()
    {
        return array(
             array(
                 'value' => Swiftype_Swiftype_Helper_Data::NO_SPELLING_OPTION,
                 'label' => 'No'
             ),
             array(
                 'value' => Swiftype_Swiftype_Helper_Data::RETRY_SPELLING_OPTION,
                 'label' => 'Yes'
             )
        );
    }
}