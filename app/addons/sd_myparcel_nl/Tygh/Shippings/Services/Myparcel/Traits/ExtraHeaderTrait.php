<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Shippings\Services\Myparcel\Traits;


trait ExtraHeaderTrait
{
    private $extra = [];

    /**
     * @return array
     */
    public function getExtraHeaders()
    {
        $extra = [
            'encoding' => 'utf-8',
            'headers' => [
                'Authorization: basic ' . base64_encode($this->getApiKey()),
                'Content-Type: application/json;charset=utf-8',
                'User-Agent: MyParcelNL-CS-Cart/' . \SD_MYPARCEL_NL_VERSION . ' (' . \PRODUCT_NAME . '/' . \PRODUCT_VERSION . ')',
            ],
        ];

        return $extra;
    }
}
