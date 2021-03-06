<?php

/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Events
 */
class Emartech_Emarsys_Model_Events extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_PostInterface
{
    /**
     * @var null|Emartech_Emarsys_Model_Resource_Event_Collection
     */
    private $_collection = null;

    /**
     * @var array
     */
    private $numericFields = [
      'event_id',
      'website_id',
      'store_id',
      'entity_id'
    ];

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     * @throws Emartech_Emarsys_Exception_NotAcceptableException
     */
    public function handlePost($request)
    {
        $sinceId = $request->getParam('since_id', 0);
        $pageSize = $request->getParam('page_size', 1000);

        $this->_validateSinceId($sinceId);

        try {
            $this
                ->_initCollection()
                ->_removeOldEvents($sinceId)
                ->_initCollection()
                ->_getEvents($sinceId)
                ->_setOrder()
                ->_setPageSize($pageSize);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return [
            'current_page' => (int)$this->_collection->getCurPage(),
            'last_page'    => (int)$this->_collection->getLastPageNumber(),
            'page_size'    => (int)$this->_collection->getPageSize(),
            'total_count'  => (int)$this->_collection->getSize(),
            'events'       => $this->_handleEvents(),
        ];
    }

    /**
     * @return array
     */
    private function _handleEvents()
    {
        $eventArray = [];
        foreach ($this->_collection as $event) {
            $eventArray[] = $this->_parseEvent($event);
        }

        return $eventArray;
    }

    /**
     * @param Emartech_Emarsys_Model_Event $event
     *
     * @return string[]
     */
    private function _parseEvent($event)
    {
        $returnArray = [];

        foreach ($event->getData() as $key => $value) {
            if (in_array($key, $this->numericFields, true)) {
                $value = (int) $value;
            }
            $returnArray[$key] = $value;
        }

        return $returnArray;
    }

    /**
     * @return $this
     */
    private function _initCollection()
    {
        $this->_collection = Mage::getResourceModel('emartech_emarsys/event_collection');

        return $this;
    }

    /**
     * @param int $sinceId
     *
     * @return $this
     */
    private function _getEvents($sinceId)
    {
        $this->_collection->addFieldToFilter('event_id', ['gt' => $sinceId]);

        return $this;
    }

    /**
     * @return $this
     */
    private function _setOrder()
    {
        $this->_collection->setOrder('event_id',  Varien_Data_Collection_Db::SORT_ORDER_ASC);

        return $this;
    }

    /**
     * @param int $pageSize
     *
     * @return $this
     */
    private function _setPageSize($pageSize)
    {
        $this->_collection->setPageSize($pageSize);

        return $this;
    }

    /**
     * @param int $beforeId
     *
     * @return $this
     */
    private function _removeOldEvents($beforeId)
    {
        $oldEvents = $this->_collection->addFieldToFilter('event_id', ['lteq' => $beforeId]);
        $oldEvents->walk('delete');

        return $this;
    }

    /**
     * @param $sinceId
     * @throws Emartech_Emarsys_Exception_NotAcceptableException
     */
    private function _validateSinceId($sinceId)
    {
        if ($this->_isSinceIdHigherThanAutoIncrement($sinceId)) {
            throw new Emartech_Emarsys_Exception_NotAcceptableException('sinceId is higher than events auto-increment');
        }
    }

    /**
     * @param $sinceId
     * @return bool
     */
    private function _isSinceIdHigherThanAutoIncrement($sinceId)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        $query = "
            SELECT
                (
                    SELECT
                        `AUTO_INCREMENT`
                    FROM
                        INFORMATION_SCHEMA.TABLES
                    WHERE
                        TABLE_SCHEMA = (
                            SELECT
                                database()
                        )
                        AND TABLE_NAME = 'emarsys_events_data'
                ) <= (
                    SELECT
                        CAST(? AS UNSIGNED)
                );
        ";

        return (bool) $readConnection->fetchOne($query, [$sinceId]);
    }
}
