<?php

class Swiftype_Swiftype_AnalyticsController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * 
     * @return \Swiftype_Swiftype_AnalyticsController
     */
    final public function logclickthroughAction()
    {
        $id = $this->getRequest()->getParam('id');
        $q = $this->getRequest()->getParam('q');
        
        $helper = Mage::helper('swiftype');
        /* @var $helper Swiftype_Swiftype_Helper_Data */
        $helper->logClickthrough((int)$id, (string)$q);
        
        return $this;
    }
    
    final public function onclickautoselectAction()
    {
        $helper = Mage::helper('swiftype');
        /* @var $helper Swiftype_Swiftype_Helper_Data */
        $helper->onClickAutoselect(
                (int)$this->getRequest()->getParam('id'),
                (string)$this->getRequest()->getParam('q'));
    }
}    