<?php

class Swiftype_Swiftype_Model_Observer_Global
{
    final public function cleanSwiftypeAutocompleteCache(Varien_Event_Observer $observer)
    {
        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array(Swiftype_Swiftype_Model_Request_Processor::CACHE_TAG));
    }
        
    final public function clearCatalogsearchResults(Varien_Event_Observer $observer)
    {
        $configData = $observer->getEvent()->getConfigData();
        /* @var $configData Mage_Core_Model_Config_Data */
        $path = $configData->getPath();
        $savedValue = $configData->getValue();
        $originalValue = Mage::getStoreConfig($path);
        
        if ($configData->getPath() == $path) {
            if ($savedValue != $originalValue) {
                if ($savedValue == Swiftype_Swiftype_Helper_Data::CATALOG_SEARCH_ENGINE_SWIFTYPE
                        || $originalValue == Swiftype_Swiftype_Helper_Data::CATALOG_SEARCH_ENGINE_SWIFTYPE) {
                    $indexer = Mage::getModel('index/indexer');
                    /* @var $indexer Mage_Index_Model_Indexer */
                    $process = $indexer->getProcessByCode('catalogsearch_fulltext');
                    $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
                    $this->_clearCatalogsearchResults();
                }
            }
        }
    }
    
    final protected function _clearCatalogsearchResults()
    {
        $resource = Mage::getModel('core/resource');
        /* @var $resource Mage_Core_Model_Resource */        
        $adapter = $resource->getConnection('core_write');
        /* @var $adapter Varien_Db_Adapter_Interface */
        $adapter->beginTransaction();
        try {
            $adapter->delete($resource->getTableName('catalogsearch/result'));
            $adapter->commit();
        } catch (Exception $ex) {
            Mage::helper('swiftype')->handleException($ex);
        }
    }
}