<?php
class Custom_Reindex_Adminhtml_ReindexController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
		$this->loadLayout()
            ->_setActiveMenu('reindex/reindex')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Reindex'), Mage::helper('adminhtml')->__('Reindex Manager'))            
            ->_title($this->__('Reindex Manager'));
		
		return $this;
    }
 
    public function indexAction()
    {
		$this->_initAction()
            ->renderLayout();
    }
	
	public function postAction()
    {
		$data = $this->getRequest()->getPost();
		if ($data['store'] && $data['index']) {
			try {
				Mage::getModel('reindex/reindex')->startReindex($data);		

                $reindexName = Mage::helper('reindex')->getReindexName($data['index']);
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("$reindexName Reindex Done Successfully."));
				$this->_redirect('*/*/index');
			} catch (Exception $e) {		
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*');
			}
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select Index and Store.'));
			$this->_redirect('*/*/index');
		}
    }
	
	public function _validateFormKey() {
		return true;
	}
}
