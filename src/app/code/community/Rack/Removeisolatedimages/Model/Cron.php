<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@principle-works.jp so we can send you a copy immediately.
 *
 * @category   System
 * @package    Rack_Removeisolatedimages
 * @copyright  Copyright (c) 2015 Veriteworks Inc. (http://principle-works.jp/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Rack_Removeisolatedimages_Model_Cron extends Mage_Core_Model_Abstract
{
    protected $_tableName;

    /**
     * Execute remove process.
     *
     */
    public function process()
    {
        if(!Mage::getStoreConfig('catalog/isolatedimages/is_active')) {
            return;
        }
        $imagePath = Mage::getBaseDir('media')."/catalog/product";
        $imageCachePath = Mage::getBaseDir('media')."/catalog/product/cache";

        $image_iterator = new RecursiveDirectoryIterator($imagePath);
        $cache_iterator = new RecursiveDirectoryIterator($imageCachePath);

        $iterator  = new RecursiveIteratorIterator($image_iterator, RecursiveIteratorIterator::SELF_FIRST);
        $iterator2 = new RecursiveIteratorIterator($cache_iterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach($iterator as $file) {

            if(preg_match('/' . str_replace('/', '\/', $imageCachePath) . '/', $file->getPathname())) {
                continue;
            }
            $dbFile = preg_replace('/' . str_replace('/', '\/', $imagePath) . '/', '', $file->getPathname());

            if(is_file($file->getPathname()) && !$this->_checkDb($dbFile)){
                foreach($iterator2 as $cache) {
                    if($file->getBasename() == $cache->getBasename()) {
                        unlink($cache->getPathname());
                    }
                }
                unlink($file->getPathname());
            }
        }
    }

    /**
     * check whether specified image exists or not.
     *
     * @param $imagePath
     * @return string
     */
    protected function _checkDb($imagePath)
    {
        $tableName = $this->_getTableName("catalog_product_entity_media_gallery");
        $sql = sprintf("SELECT count(*) FROM %s WHERE value = '%s'", $tableName, $imagePath);
        return $this->_getReadConnection()->fetchOne($sql);
    }

    /**
     * Get Wrapped Table Name
     *
     * @param string $tableName
     * @return string
     */
    protected function _getTableName($tableName)
    {
        if (!$this->_tableName) {
            /** @var $resource Mage_Core_Model_Resource */
            $resource = Mage::getSingleton('core/resource');
//            $_prefix  = (string)Mage::getModel('core/config')->getTablePrefix();
//            if (!is_null($_prefix)) {
//                $tableName = $_prefix . $tableName;
//            }
            $this->_tableName = $resource->getTableName($tableName);
        }
        return $this->_tableName;
    }


    /**
     * Read Connection
     *
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getReadConnection()
    {
        /** @var $resource Mage_Core_Model_Resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource->getConnection('core_read');
    }

    protected function _getCachedFileFromImage($image)
    {
        $parts = preg_split("/\//", $image);
        if (count($parts)){
            return $parts[count($parts) - 1];
        }

        return false;
    }

}