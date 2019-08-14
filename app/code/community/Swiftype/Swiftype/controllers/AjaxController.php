<?php

require_once(Mage::getModuleDir('controllers','Mage_CatalogSearch') . DS . 'AjaxController.php');

class Swiftype_Swiftype_AjaxController extends Mage_CatalogSearch_AjaxController
{
    public function suggestAction()
    {
        $helper = Mage::helper('swiftype');
        /* @var $helper Swiftype_Swiftype_Helper_Data */
        
        if ($helper->getUseSwiftypeAutocomplete()) {            
            $this->loadLayout();
            $this->renderLayout();
        } else {
            parent::suggestAction();
        }
        
        return $this;
    }
}