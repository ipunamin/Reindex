<?php
class Custom_Reindex_Model_Catproduct extends Mage_Catalog_Model_Resource_Category_Indexer_Product
{
    public $_storeId = null;
    public function reindexAll($postStoreId)
    {
        $this->_storeId = $postStoreId;
        $this->useIdxTable(true);
        $this->beginTransaction();
        try {
            $this->clearTemporaryIndexTable();
            $idxTable = $this->getIdxTable();
            $idxAdapter = $this->_getIndexAdapter();
            $stores = $this->_getStoresInfo();
            /**
             * Build index for store
             */
            foreach ($stores as $storeData) {
                $storeId    = $storeData['store_id'];
				if ($storeId == $this->_storeId) {

                    $websiteId  = $storeData['website_id'];
                    $rootPath   = $storeData['root_path'];
                    $rootId     = $storeData['root_id'];
                    /**
                     * Prepare visibility for all enabled store products
                     */
                    $enabledTable = $this->_prepareEnabledProductsVisibility($websiteId, $storeId);
                    /**
                     * Select information about anchor categories
                     */
                    $anchorTable = $this->_prepareAnchorCategories($storeId, $rootPath);
                    /**
                     * Add relations between not anchor categories and products
                     */
                    $select = $idxAdapter->select();
                    /** @var $select Varien_Db_Select */
                    $select->from(
                        array('cp' => $this->_categoryProductTable),
                        array('category_id', 'product_id', 'position', 'is_parent' => new Zend_Db_Expr('1'),
                            'store_id' => new Zend_Db_Expr($storeId))
                    )
                    ->joinInner(array('pv' => $enabledTable), 'pv.product_id=cp.product_id', array('visibility'))
                    ->joinLeft(array('ac' => $anchorTable), 'ac.category_id=cp.category_id', array())
                    ->where('ac.category_id IS NULL');

                    $query = $select->insertFromSelect(
                        $idxTable,
                        array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'visibility'),
                        false
                    );
                    $idxAdapter->query($query);

                    /**
                     * Assign products not associated to any category to root category in index
                     */

                    $select = $idxAdapter->select();
                    $select->from(
                        array('pv' => $enabledTable),
                        array(new Zend_Db_Expr($rootId), 'product_id', new Zend_Db_Expr('0'), new Zend_Db_Expr('1'),
                            new Zend_Db_Expr($storeId), 'visibility')
                    )
                    ->joinLeft(array('cp' => $this->_categoryProductTable), 'pv.product_id=cp.product_id', array())
                    ->where('cp.product_id IS NULL');

                    $query = $select->insertFromSelect(
                        $idxTable,
                        array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'visibility'),
                        false
                    );
                    $idxAdapter->query($query);

                    /**
                     * Prepare anchor categories products
                     */
                    $anchorProductsTable = $this->_getAnchorCategoriesProductsTemporaryTable();
                    $idxAdapter->delete($anchorProductsTable);

                    $position = 'MIN('.
                        $idxAdapter->getCheckSql(
                            'ca.category_id = ce.entity_id',
                            $idxAdapter->quoteIdentifier('cp.position'),
                            '('.$idxAdapter->quoteIdentifier('ce.position').' + 1) * '
                            .'('.$idxAdapter->quoteIdentifier('ce.level').' + 1 * 10000)'
                            .' + '.$idxAdapter->quoteIdentifier('cp.position')
                        )
                    .')';


                    $select = $idxAdapter->select()
                    ->useStraightJoin(true)
                    ->distinct(true)
                    ->from(array('ca' => $anchorTable), array('category_id'))
                    ->joinInner(
                        array('ce' => $this->_categoryTable),
                        $idxAdapter->quoteIdentifier('ce.path') . ' LIKE ' .
                        $idxAdapter->quoteIdentifier('ca.path') . ' OR ce.entity_id = ca.category_id',
                        array()
                    )
                    ->joinInner(
                        array('cp' => $this->_categoryProductTable),
                        'cp.category_id = ce.entity_id',
                        array('product_id')
                    )
                    ->joinInner(
                        array('pv' => $enabledTable),
                        'pv.product_id = cp.product_id',
                        array('position' => $position)
                    )
                    ->group(array('ca.category_id', 'cp.product_id'));
                    $query = $select->insertFromSelect($anchorProductsTable,
                        array('category_id', 'product_id', 'position'), false);
                    $idxAdapter->query($query);

                    /**
                     * Add anchor categories products to index
                     */
                    $select = $idxAdapter->select()
                    ->from(
                        array('ap' => $anchorProductsTable),
                        array('category_id', 'product_id',
                            'position', // => new Zend_Db_Expr('MIN('. $idxAdapter->quoteIdentifier('ap.position').')'),
                            'is_parent' => $idxAdapter->getCheckSql('cp.product_id > 0', 1, 0),
                            'store_id' => new Zend_Db_Expr($storeId))
                    )
                    ->joinLeft(
                        array('cp' => $this->_categoryProductTable),
                        'cp.category_id=ap.category_id AND cp.product_id=ap.product_id',
                        array()
                    )
                    ->joinInner(array('pv' => $enabledTable), 'pv.product_id = ap.product_id', array('visibility'));

                    $query = $select->insertFromSelect(
                        $idxTable,
                        array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'visibility'),
                        false
                    );
                    $idxAdapter->query($query);

                    $select = $idxAdapter->select()
                        ->from(array('e' => $this->getTable('catalog/product')), null)
                        ->join(
                            array('ei' => $enabledTable),
                            'ei.product_id = e.entity_id',
                            array())
                        ->joinLeft(
                            array('i' => $idxTable),
                            'i.product_id = e.entity_id AND i.category_id = :category_id AND i.store_id = :store_id',
                            array())
                        ->where('i.product_id IS NULL')
                        ->columns(array(
                            'category_id'   => new Zend_Db_Expr($rootId),
                            'product_id'    => 'e.entity_id',
                            'position'      => new Zend_Db_Expr('0'),
                            'is_parent'     => new Zend_Db_Expr('1'),
                            'store_id'      => new Zend_Db_Expr($storeId),
                            'visibility'    => 'ei.visibility'
                        ));

                    $query = $select->insertFromSelect(
                        $idxTable,
                        array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'visibility'),
                        false
                    );

                    $idxAdapter->query($query, array('store_id' => $storeId, 'category_id' => $rootId));

                    break;
                }
            }

            $this->syncData();

            /**
             * Clean up temporary tables
             */
            $this->clearTemporaryIndexTable();
            $idxAdapter->delete($enabledTable);
            $idxAdapter->delete($anchorTable);
            $idxAdapter->delete($anchorProductsTable);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
        return $this;
	}

    /**
     * Clean up temporary index table
     *
     */
    public function clearTemporaryIndexTable()
    {
        $adapter = $this->_getWriteAdapter();
        $where = $adapter->quoteInto('store_id=?', $this->_storeId);
        $adapter->delete($this->getIdxTable(), $where);
    }

    /**
     * Synchronize data between index storage and original storage
     *
     * @return Mage_Index_Model_Resource_Abstract
     */
    public function syncData()
    {
        $this->beginTransaction();
        try {
            /**
             * Can't use truncate because of transaction
             */
            $adapter = $this->_getWriteAdapter();
            $where = $adapter->quoteInto('store_id=?', $this->_storeId);
            $adapter->delete($this->getMainTable(), $where);
            $this->insertFromTable($this->getIdxTable(), $this->getMainTable(), false);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
        return $this;
    }
}