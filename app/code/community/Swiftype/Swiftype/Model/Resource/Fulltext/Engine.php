<?php

class Swiftype_Swiftype_Model_Resource_Fulltext_Engine
    extends Mage_CatalogSearch_Model_Resource_Fulltext_Engine
{
    private $_documentFieldTypes = array();

    /**
     * Create/update Documents in Swiftype
     *
     * @param int $storeId
     * @param array $entityIndexes
     * @param string $entity
     * @return \Swiftype_Swiftype_Model_Resource_Fulltext_Engine
     */
    final public function saveEntityIndexes($storeId, $entityIndexes, $entity = 'product')
    {
        if ($entity == 'product') {            
            $bulkCreateOrUpdate = new stdClass();
            $bulkCreateOrUpdate->auth_token = $this->_getHelper()->getAuthToken($storeId);

            foreach ($entityIndexes as $entityId => $index) {
                $document = new stdClass();
                $document->external_id = $entityId;

                foreach ($index as $attributeCode => $value) {
                    $document->fields[] = array(
                        'name' => $attributeCode,
                        'value' => $value,
                        'type' => $this->_getDocumentFieldType($attributeCode)
                    );
                }
                $document->fields[] = array(
                    'name' => 'product_id',
                    'value' => $entityId,
                    'type' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_INTEGER
                );
                $bulkCreateOrUpdate->documents[] = $document;
            }

            $swiftypeApiParameters = array(
                'uri' => array(
                    'engines' => $this->_getHelper()->getEngineSlug($storeId),
                    'document_types' => $entity,
                    'documents' => 'bulk_create_or_update_verbose'
                ),
                'raw_data' => array(
                    'enctype' => 'application/json',
                    'data' => Zend_Json::encode($bulkCreateOrUpdate)
                )
            );
            
            $swiftypeApiClient = $this->_getHelper()->getSwiftypeClient($swiftypeApiParameters);
            $swiftypeApiResponse = $swiftypeApiClient->request(Zend_Http_Client::POST);

            if ($swiftypeApiResponse->getStatus() == 200) {
                $responseMessage = Zend_Json::decode($swiftypeApiResponse->getBody());
                if ($responseMessage[0] !== true) {
                    $this->_logError($storeId, $swiftypeApiParameters, array_keys($entityIndexes), $swiftypeApiResponse);
                }
            } else {
                $this->_logError($storeId, $swiftypeApiParameters, array_keys($entityIndexes), $swiftypeApiResponse);
            }           
        }
        
        return $this;
    }
    
    final protected function _logError($storeId, $swiftypeApiParameters, $productIds, $swiftypeApiResponse)
    {
        Mage::helper('swiftype/log')->logIndexRequest($storeId, $swiftypeApiParameters, $productIds);
        Mage::helper('swiftype/log')->logIndexResponse($storeId, $swiftypeApiParameters, $swiftypeApiResponse);
    }

    /**
     * Get Swiftype Document Field Type for Attribute Code
     *
     * @param string $attributeCode
     * @return string
     */
    final protected function _getDocumentFieldType($attributeCode)
    {
        if (!in_array($attributeCode, $this->_documentFieldTypes)) {
            $entityType = Mage::getModel('eav/entity_type')
                    ->loadByCode(Mage_Catalog_Model_Product::ENTITY);
            /* @var $entityType Mage_Eav_Model_Entity_Type */
            $attribute = Mage::getModel('catalog/resource_eav_attribute')
                    ->loadByCode($entityType->getId(), $attributeCode);
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */

            if ($attribute->getId()) {
                $documentFieldType = $attribute->getSwiftypeDocumentFieldType();
            } else {
                $documentFieldType = Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_TEXT;
            }
            $this->_documentFieldTypes[$attributeCode] = $documentFieldType;
        }
        return $this->_documentFieldTypes[$attributeCode];
    }

    /**
     * @return Swiftype_Swiftype_Helper_Data
     */
    final protected function _getHelper()
    {
        return Mage::helper('swiftype');
    }

    /**
     * Remove Document from Swiftype
     *
     * @param mixed $storeId
     * @param mixed $entityId
     * @param string $entity
     * @return \Swiftype_Swiftype_Model_Resource_Fulltext_Engine
     */
    final public function cleanIndex($storeId = null, $entityId = null, $entity = 'product')
    {
        if ($entity == 'product' && $entityId) {
            if (!is_array($entityId)) {
                $swiftypeApiParameters = array(
                    'uri' => array(
                        'engines' => $this->_getHelper()->getEngineSlug($storeId),
                        'document_types' => $entity,
                        'documents' => $entityId
                    ),
                    'get' => array(
                        'auth_token' => $this->_getHelper()->getAuthToken($storeId)
                    )

                );
                $swiftypeApiClient = $this->_getHelper()->getSwiftypeClient($swiftypeApiParameters);
                $swiftypeApiResponse = $swiftypeApiClient->request(Zend_Http_Client::DELETE);

                if ($swiftypeApiResponse->getStatus() == 200 || $swiftypeApiResponse->getStatus() == 406) {
                    return $this;
                }

                Mage::helper('swiftype/log')->logDeleteResponse($storeId, $swiftypeApiParameters, $swiftypeApiResponse);
            }
        }
        return $this;
    }

    /**
     *
     * @param array $index Data to index
     * @param string $separator
     * @return array Data formatted ready for indexing
     */
    final public function prepareEntityIndex($index, $separator = ' ')
    {
        $separator = ' ';
        foreach ($index as $attributeCode => $value) {
            if (is_array($value)) {
                reset($value);
                $value = $value[key($value)];
            }
            if (preg_match('/^(Enabled)?$/', $value) || preg_match('/^(in_stock|price)?$/', $attributeCode)) {
                unset($index[$attributeCode]);
            } else {
                $index[$attributeCode] = $value;
            }
        }
        return $index;
    }
}