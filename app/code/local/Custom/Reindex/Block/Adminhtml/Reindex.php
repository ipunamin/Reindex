<?php
class Custom_Reindex_Block_Adminhtml_Reindex extends Mage_Adminhtml_Block_Template
{
	public function getAllIndexs()
	{
		$processes = array();
		$collection = Mage::getSingleton('index/indexer')->getProcessesCollection();
		foreach ($collection as $process) {
			$id = $process['process_id']; 
			if ($id == 1 || $id == 3 || $id == 4 || $id == 5 || $id == 6 || $id == 7) {
				$processes[$id] = $process->getIndexer()->getName();
			}			
		}
		return $processes;
	}
}
