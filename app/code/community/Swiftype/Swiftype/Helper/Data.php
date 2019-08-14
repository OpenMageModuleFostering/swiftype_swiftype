<?php

class Swiftype_Swiftype_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    const CATALOG_SEARCH_ENGINE_SWIFTYPE = 'swiftype/fulltext_engine';
    const CATALOG_SEARCH_ENGINE_DEFAULT = 'catalogsearch/fulltext_engine';

    const API_URL = 'https://api.swiftype.com/api/v1';

    const DOCUMENT_TYPE = 'product';
    const DOCUMENT_FIELD_TYPE_STRING = 'string';
    const DOCUMENT_FIELD_TYPE_TEXT = 'text';
    const DOCUMENT_FIELD_TYPE_ENUM = 'enum';
    const DOCUMENT_FIELD_TYPE_INTEGER = 'integer';
    const DOCUMENT_FIELD_TYPE_FLOAT = 'float';
    const DOCUMENT_FIELD_TYPE_DATE = 'date';
    const DOCUMENT_FIELD_TYPE_LOCATION = 'location';
    
    const NO_SPELLING_OPTION = 'no';
    const RETRY_SPELLING_OPTION = 'retry';

    final public function handleException(Exception $exception, $throw = false, $log = true)
    {
        if ($log) {
            $this->getSwiftypeLogger($exception->getMessage());
        }

        if (Mage::getIsDeveloperMode() || $throw) {
            throw $exception;
        }
    }


    final public function onClickAutoselect($productId, $queryText)
    {
        $client = new Zend_Http_Client(self::API_URL . '/public/analytics/pas');
        $client->setParameterGet('prefix', $queryText);
        $client->setParameterGet('engine_key', $this->getEngineSlug());
        $client->setParameterGet('entry_id', $productId);
        $client->setParameterGet('document_type_id', 'product');
        $client->request(Zend_Http_Client::GET);
    }
    /**
     * Autocomplete click tracking.
     *
     * This function is automatically added to clicks coming from autocomplete
     * product clicks.
     *
     */
    final public function getOnClickAutoselect(Mage_Catalog_Model_Product $product)
    {
        if ($onClickAutoselectUrl = $this->_getOnClickAutoselectUrl($product)) {
            $onClickAutoselect = "new Ajax.Request('$onClickAutoselectUrl', {method: 'get', asynchronous: false})";
        }
        return isset($onClickAutoselect) ? $onClickAutoselect : '';
    }

    final protected function _getOnClickAutoselectUrl(Mage_Catalog_Model_Product $product)
    {
        if ($queryText = Mage::helper('catalogsearch')->getQueryText()) {
            $onClickAutoselectUrl = Mage::getUrl('swiftype/analytics/onclickautoselect', array(
                'id' => $product->getId(),
                'q' => $queryText
            ));
        }
        return isset($onClickAutoselectUrl) ? $onClickAutoselectUrl : null;
    }

    /**
     * Search result click tracking.
     *
     * If you want to use it in search result pages, you need to
     * include this in the HTML elements you want to track clicks on.
     *
     * In practice, this will be product links in the catalog/product/list.phtml
     * file that your Package/Theme uses. To locate this file, turn on Template
     * Hints in the System Configuration, and perform a test search. It will
     * highlight the location of catalog/product/list.phtml
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string Contents of onClick if page is search result, empty string otherwise.
     */
    final public function getOnClick(Mage_Catalog_Model_Product $product)
    {
        $onClick = '';

        if ($onClickUrl = $this->_getOnClickUrl($product)) {
            $onClick = "new Ajax.Request('$onClickUrl', {method: 'get', asynchronous: false})";
        }

        return $onClick;
    }

    /**
     * Get URL for Click Tracking
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string|null onClick URL if page is search result, null otherwise.
     */
    final protected function _getOnClickUrl(Mage_Catalog_Model_Product $product)
    {
        $onClickUrl = null;
        $queryText = Mage::helper('catalogsearch')->getQueryText();

        if (!empty($queryText)) {

            $onClickUrl = Mage::getUrl('swiftype/analytics/logclickthrough', array(
                'id' => $product->getId(),
                'q' => $queryText
            ));
        }

        return $onClickUrl;
    }

    /**
     *
     * @param int $productId
     * @param string $query
     */
    final public function logClickthrough($productId, $query)
    {
        $client = $this->getSwiftypeClient(array(
            'uri' => array(
                'engines' => $this->getEngineSlug(),
                'document_types' => self::DOCUMENT_TYPE,
                'analytics' => 'log_clickthrough.json'
            ),
            'raw_data' => array(
                'enctype' => 'application/json',
                'data' => Zend_Json::encode(array(
                    'auth_token' => $this->getAuthToken(),
                    'id' => (int)$productId,
                    'q' => (string)$query
                ))
            )
        ));

        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() != 200) {
            Mage::throwException($response->getStatus());
        }
    }

    final public function getCacheKey()
    {
        $requestProcessor = Mage::getSingleton('swiftype/request_processor');
        /* @var $requestProcessor Swiftype_Swiftype_Model_Request_Processor */

        return $requestProcessor->getCacheKey();
    }

    final public function canCacheSwiftypeAutocomplete(Mage_Core_Controller_Request_Http $request = null)
    {
        if (!$this->getUseSwiftypeAutocomplete()
                || !Mage::app()->useCache(Swiftype_Swiftype_Model_Request_Processor::CACHE_KEY_PREFIX)) {
            return false;
        }

        if (!$request) {
            $request = Mage::app()->getRequest();
            /* @var $request Mage_Core_Controller_Request_Http */
        }

        if ($request->getModuleName() == 'catalogsearch'
                && $request->getControllerName() == 'ajax'
                && $request->getActionName() == 'suggest') {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return array
     */
    final public function getSuggestedProducts()
    {
        $productsAsArray = array();
        $products = Mage::getModel('catalog/product')->getCollection();
        /* @var $products Mage_Catalog_Model_Resource_Product_Collection */

        $products->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $products->addFieldToFilter('visibility', array('in' => array(
            Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
        )));       

        $queryText = Mage::helper('catalogsearch')->getQueryText();
        $queryText = !empty($queryText) ? $queryText : null;

        if ($this->getUseSwiftype() && $queryText) {
            $client = $this->getSwiftypeClient(array(
               'uri' => array(
                    'public' => 'engines',
                    'suggest' => null
                ),
                'get' => array(
                    'q' => $queryText,
                    'engine_key' => $this->getEngineKey()
                )
            ));

            $response = $client->request(Zend_Http_Client::GET);

            if ($response->getStatus() != 200) {
                Mage::throwException($response->getMessage());
            }

            $records = Zend_Json::decode($response->getBody(), Zend_Json::TYPE_OBJECT);
            $productIds = array();

            foreach ($records->records->product as $product) {
                $productIds[] = $product->product_id;
            }

            if (!empty($productIds)) {                
                $products = $products->addAttributeToSelect('*')
                        ->addFieldToFilter('entity_id', array('in' => $productIds));             
                
                foreach ($productIds as $productId) {
                    if ($product = $products->getItemById($productId)) {
                        $productsAsArray[$productId] = $product;
                    }                        
                }
            }

        }

        return array_slice($productsAsArray, 0, $this->getAutocompleteLimit());
    }

    /**
     * Return HTTP client ready to interact with Swiftype
     *
     * @param mixed $store
     * @param array $params
     * @return Zend_Http_Client $client
     */
    final public function getSwiftypeClient(array $params = array())
    {
        $uri = Swiftype_Swiftype_Helper_Data::API_URL;

        foreach ($params['uri'] as $name => $value) {
            $uri .= '/' . urlencode($name);
            if ($value) {
                $uri .= '/' . urlencode($value);
            }
        }

        $client = new Zend_Http_Client($uri);

        if (isset($params['raw_data'])) {
            $client->setRawData($params['raw_data']['data'], $params['raw_data']['enctype']);
        }

        if (isset($params['get'])) {
            foreach ($params['get'] as $name => $value) {
                $client->setParameterGet($name, $value);
            }
        }

        return $client;
    }

    /**
     * Logger for Swiftype-related events
     */
    final public function getSwiftypeLogger($message)
    {
        $filename = 'swiftype-'.date('m-d-y').'.log';
        $message = is_string($message) ? "swiftype\\".$message : $message;
        Mage::log($message, null, $filename, true);
    }

    final public function getUseSwiftype($store = null)
    {
        if (Mage::getStoreConfig('catalog/search/engine', $store) == self::CATALOG_SEARCH_ENGINE_SWIFTYPE) {
            return true;
        } else {
            return false;
        }
    }

    final public function getDocumentType()
    {
        return self::DOCUMENT_TYPE;
    }

    final public function getAuthToken($store = null)
    {
        return $this->getApiKey($store);
    }

    final public function getApiKey($store = null)
    {
        return preg_replace('/[^A-Za-z0-9\-_]/', '', Mage::getStoreConfig('catalog/search/swiftype_api_key', $store));
    }

    final public function getEngineSlug($store = null)
    {
        return preg_replace('/[^A-Za-z0-9\-_]/', '', Mage::getStoreConfig('catalog/search/swiftype_engine_slug', $store));
    }

    final public function getEngineKey($store = null)
    {
        return preg_replace('/[^A-Za-z0-9\-_]/', '', Mage::getStoreConfig('catalog/search/swiftype_engine_key', $store));
    }

    final public function getAutocompleteLimit($store = null)
    {
        return Mage::getStoreConfig('catalog/search/swiftype_autocomplete_limit', $store);
    }
    
    final public function getSpelling($store = null)
    {
        if (!$this->getUseSwiftype()) {
            return false;
        }
        
        return Mage::getStoreConfig('catalog/search/swiftype_spelling', $store);
    }
    
    final public function getUseSwiftypeAutocomplete($store = null)
    {
        if (!$this->getUseSwiftype()) {
            return false;
        }

        return Mage::getStoreConfig('catalog/search/swiftype_autocomplete', $store);
    }
}
