<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Interface Emartech_Emarsys_Model_Abstract_DeleteInterface
 */
interface Emartech_Emarsys_Model_Abstract_DeleteInterface
{
    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     */
    public function handleDelete($request);
}
