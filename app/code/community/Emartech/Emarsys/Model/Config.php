<?php

/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Config
 */
class Emartech_Emarsys_Model_Config extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_PostInterface
{

    /**
     * @var array
     */
    private $_convertableKeys = [
        'inject_snippet' => Emartech_Emarsys_Helper_Config::INJECT_WEBEXTEND_SNIPPETS,
    ];

    /**
     * @var array
     */
    private $_defaultConfig = [
        Emartech_Emarsys_Helper_Config::CUSTOMER_EVENTS           => Emartech_Emarsys_Helper_Config::CONFIG_DISABLED,
        Emartech_Emarsys_Helper_Config::SALES_EVENTS              => Emartech_Emarsys_Helper_Config::CONFIG_DISABLED,
        Emartech_Emarsys_Helper_Config::MARKETING_EVENTS          => Emartech_Emarsys_Helper_Config::CONFIG_DISABLED,
        Emartech_Emarsys_Helper_Config::INJECT_WEBEXTEND_SNIPPETS => Emartech_Emarsys_Helper_Config::CONFIG_DISABLED,
        Emartech_Emarsys_Helper_Config::MERCHANT_ID               => Emartech_Emarsys_Helper_Config::CONFIG_EMPTY,
        Emartech_Emarsys_Helper_Config::SNIPPET_URL               => Emartech_Emarsys_Helper_Config::CONFIG_EMPTY,
    ];

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     */
    public function handlePost($request)
    {
        $websiteId = $request->getParam('website_id', 0);
        $config = $request->getParam('config', $this->_defaultConfig);

        try {

            $foundDifference = false;

            foreach ($config as $key => $value) {
                if (array_key_exists($key, $this->_convertableKeys)) {
                    $key = $this->_convertableKeys[$key];
                }
                if ($this->setConfigValue($key, $value, $websiteId)) {
                    $foundDifference = true;
                }
            }
            if ($foundDifference) {
                $this->cleanScope();
            }
            $status = 'ok';

        } catch (Exception $e) {
            Mage::logException($e);
            $status = 'error';
        }

        return ['status' => $status];
    }

    /**
     * @param string $xmlPostPath
     * @param string $value
     * @param int    $scopeId
     * @param string $scope
     *
     * @return bool
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function setConfigValue($xmlPostPath, $value, $scopeId, $scope = Emartech_Emarsys_Helper_Config::SCOPE_TYPE_DEFAULT)
    {
        $xmlPath = Emartech_Emarsys_Helper_Config::XML_PATH_STORE_CONFIG_PRE_TAG . trim($xmlPostPath, '/');

        if (is_array($value)) {
            $value = json_encode($value);
        }

        switch ($scope) {
            case Emartech_Emarsys_Helper_Config::SCOPE_TYPE_DEFAULT:
                $oldConfigValue = Mage::app()->getWebsite($scopeId)->getConfig($xmlPath);
                break;
            default:
                $oldConfigValue = Mage::app()->getStore($scopeId)->getConfig($xmlPath);
                break;
        }

        if ($oldConfigValue == $value) {
            return false;
        }

        Mage::app()->getConfig()->saveConfig($xmlPath, $value, $scope, $scopeId);

        return true;
    }

    /**
     * @return void
     */
    public function cleanScope()
    {
        Mage::app()->getCacheInstance()->cleanType('config');
        Mage::dispatchEvent('adminhtml_cache_refresh_type', ['type' => 'config']);
        Mage::app()->getConfig()->reinit();
    }
}
