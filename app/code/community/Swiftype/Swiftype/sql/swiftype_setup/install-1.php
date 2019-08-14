<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->getConnection()->addColumn(
        $installer->getTable('catalog/eav_attribute'),
        'swiftype_document_field_type',
        array(
            'TYPE' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'LENGTH' => 255,
            'COMMENT' => 'Swiftype Document Field Type.',
            'DEFAULT' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_STRING
        ));

$attributes = Mage::getResourceModel('catalog/product_attribute_collection');
/* @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
$attributes->addSearchableAttributeFilter();

foreach ($attributes as $attribute) {
    /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
    
    $inputType = $attribute->getFrontend()->getInputType();
    
    switch ($inputType) {
        case 'boolean':
            $swiftypeDocumentFieldType = Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_INTEGER;
            break;
        case 'date':
        case 'datetime':
            $swiftypeDocumentFieldType = Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_DATE;
            break;
        case 'multiselect':
        case 'select':
        case 'text':
            $swiftypeDocumentFieldType = Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_STRING;
            break;
        case 'price':
        case 'weight':
            $swiftypeDocumentFieldType = Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_FLOAT;
            break;
        case 'textarea':
            $swiftypeDocumentFieldType = Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_TEXT;
            break;
        default:
            $swiftypeDocumentFieldType = Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_TEXT;
            break;
    }
    
    $attribute->setData('swiftype_document_field_type', $swiftypeDocumentFieldType);
    $attribute->save();
}

$installer->endSetup();