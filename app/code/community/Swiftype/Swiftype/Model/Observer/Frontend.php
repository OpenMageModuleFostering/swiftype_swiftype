<?php

class Swiftype_Swiftype_Model_Observer_Frontend
{
    /**
     * @return Swiftype_Swiftype_Helper_Data
     */
    final protected function _getHelper()
    {
        return Mage::helper('swiftype');
    }
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     * @return Swiftype_Swiftype_Model_Observer_Frontend
     */
    final public function cacheSwiftypeAutoComplete(Varien_Event_Observer $observer)
    {
        if ($this->_getHelper()->canCacheSwiftypeAutocomplete()) {
            $buffer = ob_get_clean();    
            
            $cache = Mage::app()->getCache();
            $cacheKey = $this->_getHelper()->getCacheKey();
            
            if (!empty($buffer)) {
                $cache->save($buffer, $cacheKey, array(Swiftype_Swiftype_Model_Request_Processor::CACHE_TAG));
            }
           
            echo $buffer;           
        }
        
        return $this;
    }       
}