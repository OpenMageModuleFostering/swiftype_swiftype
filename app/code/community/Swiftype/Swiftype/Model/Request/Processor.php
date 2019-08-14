<?php

class Swiftype_Swiftype_Model_Request_Processor
{
    const CACHE_KEY_PREFIX = 'swiftype_autocomplete';
    const CACHE_TAG = 'SWIFTYPE_AUTOCOMPLETE';
    
    final public function extractContent($content = false)
    {
        $cache = Mage::app()->getCache();
        $content = $cache->load($this->getCacheKey());

        if (!$content) {
            ob_start();
        }
                             
        return $content;
    }
    
    /**
     * 
     * @param Mage_Core_Controller_Request_Http $request
     * @return null|string
     */
    final public function getCacheKey(Mage_Core_Controller_Request_Http $request = null)
    {
        if (!$request) {
            $request = Mage::app()->getRequest();
            /* @var $request Mage_Core_Controller_Request_Http */
        }
        
        return self::CACHE_KEY_PREFIX . '_' . md5($request->getRequestUri());        
    }
}