<?php
class Custom_Reindex_Model_Reindex extends Mage_Core_Model_Abstract
{
    public function startReindex($data)
	{
        if ($data['index'] == 1) { //Product Attributes
            Mage::getModel('reindex/eav_source')->reindexAll($data['store']);
        }

        if ($data['index'] == 3) { //Catalog Url Rewrites
			Mage::getSingleton('catalog/url')->refreshRewrites($data['store']);			
		}
		
		if ($data['index'] == 4) { //Catalog Product Flat		
			Mage::getResourceModel('catalog/product_flat_indexer')->rebuild($data['store']);
		}
		
		if ($data['index'] == 5) { //Catalog Category Flat			
			$store = Mage::getModel('core/store')->load($data['store']);
			Mage::getResourceModel('catalog/category_flat')->rebuild($store);
		}
		
		if ($data['index'] == 6) { //Category Products
			Mage::getModel('reindex/catproduct')->reindexAll($data['store']);
		}
		
		if ($data['index'] == 7) { //Catalog Search Index
			Mage::getResourceModel('catalogsearch/fulltext')->rebuildIndex($data['store']);
		}
	}
}