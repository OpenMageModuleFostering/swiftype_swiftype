<?php

class Swiftype_Swiftype_Model_Resource_Fulltext
    extends Mage_CatalogSearch_Model_Resource_Fulltext
{
    const PER_PAGE = 20;
    
    final public function cleanIndex($storeId = null, $productId = null)
    {
        if ($storeId) {
            $this->_setStoreEngine($storeId);
            parent::cleanIndex($storeId, $productId);
        } else {
            foreach (Mage::app()->getStores() as $store) {
                $this->cleanIndex($store->getId(), $productId);
            }
        }

        return $this;
    }

    final protected function _rebuildStoreIndex($storeId, $productIds = null)
    {
        $this->_setStoreEngine($storeId);

        return parent::_rebuildStoreIndex($storeId, $productIds);
    }

    final protected function _setStoreEngine($storeId)
    {
        $this->_engine = $this->_getStoreEngine($storeId);
    }

    final protected function _getStoreEngine($storeId)
    {
        $engine = $this->_getHelper()->getUseSwiftype($storeId) ?
                Swiftype_Swiftype_Helper_Data::CATALOG_SEARCH_ENGINE_SWIFTYPE
                : Swiftype_Swiftype_Helper_Data::CATALOG_SEARCH_ENGINE_DEFAULT;

        return Mage::getResourceSingleton($engine);
    }

    final protected function _getSearchableProducts($storeId, array $staticFields, $productIds = null, $lastProductId = 0, $limit = 100)
    {
        if ($this->_getHelper()->getUseSwiftype($storeId)) {
            $limit = null;
        }

        return parent::_getSearchableProducts($storeId, $staticFields, $productIds, $lastProductId, $limit);
    }

    /**
     * @return Swiftype_Swiftype_Helper_Data
     */
    final protected function _getHelper()
    {
        return Mage::helper('swiftype');
    }

    public function prepareResult($object, $queryText, $query)
    {
        $storeId = $query->getStoreId();

        if (!$this->_getHelper()->getUseSwiftype($storeId)) {
            return parent::prepareResult($object, $queryText, $query);
        } else {
            $this->_updateSearchResults($query, $this->_prepareResults($this->_getResults($query), $query));
            $query->setIsProcessed(1);
            return $this;
        }
    }
    
    /**
     * 
     * @param Mage_CatalogSearch_Model_Query $query
     * @return array
     */
    final protected function _getResults(Mage_CatalogSearch_Model_Query $query)
    {
        $results = array();
        $result = $this->_getResult($query);
        $results = $result->records->product;
        
        if ($result->info->product->num_pages > 1) {
            for ($page = 2; $page <= $result->info->product->num_pages; $page++) {
                $result = $this->_getResult($query, $page);
                $results = array_merge($results, $result->records->product);
            }
        }
        
        return $results;
    }
    
    /**
     * 
     * @param Mage_CatalogSearch_Model_Query $query
     * @param int $page
     * @return array
     */
    final protected function _getResult(Mage_CatalogSearch_Model_Query $query, $page = 1)
    {
        $storeId = $query->getStoreId();
        
        $swiftypeApiParameters = array(
            'uri' => array(
                'engines' => $this->_getHelper()->getEngineSlug($storeId),
                'document_types' => Swiftype_Swiftype_Helper_Data::DOCUMENT_TYPE,
                'search' => null
            ),
            'get' => array(
                'auth_token' => $this->_getHelper()->getAuthToken($storeId),
                'q' => $this->_getQueryText($query),
                'per_page' => self::PER_PAGE,
                'page' => $page
            )
        );

        $swiftypeApiClient = $this->_getHelper()->getSwiftypeClient($swiftypeApiParameters);
        $swiftypeApiResponse = $swiftypeApiClient->request(Zend_Http_Client::GET);

        if ($swiftypeApiResponse->getStatus() == 200) {
            return Zend_Json::decode($swiftypeApiResponse->getBody(), Zend_Json::TYPE_OBJECT);
        } else {
            Mage::helper('swiftype/log')->logSearchException($storeId, $swiftypeApiParameters, 'Status: ' . $swiftypeApiResponse->getStatus());
        }
    }

    /**
     * 
     * @param array $results
     * @param Mage_CatalogSearch_Model_Query $query
     * @return array
     */
    final protected function _prepareResults(array $results, Mage_CatalogSearch_Model_Query $query)
    {
        $preparedResults = array();
        $resultCount = count($results);
        $customRelevance = false;

        foreach ($results as $result) {
            /* @var $result stdClass */
            $relevance = isset($result->_score) ? $result->_score : 0;
            // Indicates custom ordering
            if (!$relevance) {
                $customRelevance = true;
                break;
            }
        }

        foreach ($results as $result) {
            /* @var $result stdClass */
            $relevance = isset($result->_score) ? $result->_score : 0;

            if ($customRelevance) {
                $relevance = $resultCount;
                $resultCount--;
            }

            $preparedResults[] = array(
                'product_id' => (int)$result->product_id,
                'query_id' => (int)$query->getId(),
                'relevance' => $relevance
            );
        }

        return $preparedResults;
    }

    final protected function _updateSearchResults(Mage_CatalogSearch_Model_Query $query, array $results)
    {
        $adapter = $this->_getWriteAdapter();
        $table = $this->getTable('catalogsearch/result');

        $adapter->beginTransaction();
        $adapter->delete($table, array('query_id = ?' => $query->getId()));

        $failedResults = array();
        $relevance = array('relevance');
        foreach ($results as $result) {
            try {
                $adapter->insertOnDuplicate($table, $result, $relevance);
            } catch (Exception $exception) {
                if (!isset($failedResults[$exception->getMessage()])) {
                    $failedResults[$exception->getMessage()] = array();
                }
                $failedResults[$exception->getMessage()][] = $result['product_id'];
            }
        }
        $adapter->commit();

        if(!empty($failedResults)) {
            Mage::helper('swiftype/log')->logSearchException($query->getStoreId(), null, Zend_Json::encode($failedResults));
        }
    }

    final protected function _getQueryText(Mage_CatalogSearch_Model_Query $query)
    {
        $queryText = $query->getQueryText();

        if ($query->getSynonymFor()) {
            $queryText = $query->getSynonymFor();
        }

        return $queryText;
    }
}