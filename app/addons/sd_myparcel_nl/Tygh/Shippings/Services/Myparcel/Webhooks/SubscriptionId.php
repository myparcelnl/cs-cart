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

namespace Tygh\Shippings\Services\Myparcel\Webhooks;

use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;

/**
 * Class SubscriptionId
 * Implements specific API datatype
 * @package Tygh\Shippings\Services\Myparcel\Webhooks
 */
class SubscriptionId
{
    use ExceptionsTrait;

    /**
     * @var int
     */
    private $id = 0;

    /**
     * SubscriptionId constructor.
     * @param $id
     */
    public function __construct($id)
    {
        if (empty($id)) {
            $this->throwConstructorParamsException();
        }
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
