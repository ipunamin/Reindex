<?php
class Custom_Reindex_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getReindexName($reindexCode)
    {
        if ($reindexCode == 1) {
            return "Product Attributes";
        } elseif ($reindexCode == 3) {
            return "Catalog Url Rewrites";
        } elseif ($reindexCode == 4) {
            return "Catalog Product Flat";
        } elseif ($reindexCode == 5) {
            return "Catalog Category Flat";
        } elseif ($reindexCode == 6) {
            return "Category Products";
        } elseif ($reindexCode == 7) {
            return "Catalog Search Index";
        }
    }
}