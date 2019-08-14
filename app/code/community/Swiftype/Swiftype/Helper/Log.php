<?php

class Swiftype_Swiftype_Helper_Log
    extends Mage_Core_Helper_Abstract
{
    final protected function _log($key, $message)
    {
        $logDir = Mage::getBaseDir('log') . DS . 'swiftype';
        if (!is_readable($logDir)) {
            mkdir($logDir, 0777);
        }
        Mage::log("$key: $message", null, 'swiftype/swiftype-' . date('d-m-Y') . '.log', true);
    }
    
    final protected function _getLogKey($storeId, array $swiftypeApiUri)
    {
        $logKey = '';
        foreach ($swiftypeApiUri as $name => $value) {
            $logKey .= "/$name";
            if ($value) {
                $logKey .= "/$value";
            }
        }
        return "[$storeId] $logKey";
    }
    
    final public function logIndexRequest($storeId, array $swiftypeApiParameters, $productIds)
    {
        $this->_log($this->_getLogKey($storeId, $swiftypeApiParameters['uri']), 'Product IDs: ' . implode(',', $productIds));
    }
    
    final public function logIndexResponse($storeId, array $swiftypeApiParameters, Zend_Http_Response $swiftypeApiResponse)
    {
        $this->_log($this->_getLogKey($storeId, $swiftypeApiParameters['uri']), 'Status: ' . $swiftypeApiResponse->getStatus());
    }
    
    final public function logDeleteResponse($storeId, array $swiftypeApiParameters, Zend_Http_Response $swiftypeApiResponse)
    {
        $this->_log($this->_getLogKey($storeId, $swiftypeApiParameters['uri']), 'Method: Delete, Status: ' . $swiftypeApiResponse->getStatus());
    }
    
    final public function logSearchException($storeId, array $swiftypeApiParameters = array('uri' => array('search' => null)), $exceptionMessage)
    {
        $this->_log($this->_getLogKey($storeId, $swiftypeApiParameters['uri']), $exceptionMessage);
    }
}