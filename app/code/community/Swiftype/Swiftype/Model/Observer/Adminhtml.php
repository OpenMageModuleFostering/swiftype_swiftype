<?php

final class Swiftype_Swiftype_Model_Observer_Adminhtml
{
    /**
     * Add Swiftype Document Field Types to Attribute Edit Form
     * 
     * @param Varien_Event_Observer $observer
     * @return \Swiftype_Swiftype_Model_Observer_Adminhtml
     */
    final public function adminhtmlCatalogProductAttributeEditPrepareForm(Varien_Event_Observer $observer)
    {
        $form = $observer->getEvent()->getForm();
        /* @var $form Varien_Data_Form */
        
        $fieldset = $form->addFieldset('swiftype_fieldset',
            array('legend' => 'Swiftype Properties')
        );        
        $fieldset->addField('swiftype_document_field_type', 'select', array(
            'name' => 'swiftype_document_field_type',
            'label' => 'Document Field Type',
            'title' => 'Document Field Type',
            'note' => 'See <a href="https://swiftype.com/documentation/overview#field_types" target="_blank">Document Field Types</a>.',
            'values' => array(
                array('value' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_STRING, 'label' => 'String'),
                array('value' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_TEXT, 'label' => 'Text'),
                array('value' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_ENUM, 'label' => 'Enum'),
                array('value' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_INTEGER, 'label' => 'Integer'),
                array('value' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_FLOAT, 'label' => 'Float'),
                array('value' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_DATE, 'label' => 'Date'),
                array('value' => Swiftype_Swiftype_Helper_Data::DOCUMENT_FIELD_TYPE_LOCATION, 'label' => 'Location')
            ),
        ));
        return $this;        
    }
}