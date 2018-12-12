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

namespace Tygh\Shippings\Services\Myparcel\Delivery;

/**
 * Class DeliveryOptionTime
 * Describe delivery option type
 * @todo: remove this class if not used
 *
 * @package Tygh\Shippings\Services\Myparcel\Delivery
 */
class DeliveryOptionTime
{
    /**
     * @var string
     * Data type: time
     * Required: n/a.
     */
    private $start = '',

        /**
         * @var string
         * Data type: time
         * Required: n/a.
         */
        $end = '',

        /**
         * @var string
         * Data type: price
         * Required: n/a.
         */
        $price = 0.00,

        /**
         * @var string
         * Data type: string
         * Required: n/a.
         */
        $price_comment = '',

        /**
         * @var string
         * Data type: string
         * Required: n/a.
         */
        $comment = '',

        /**
         * @var string
         * Data type: integer
         * Required: n/a.
         */
        $type = 0;

    /**
     * @return string
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param string $start
     * @return DeliveryOptionTime
     */
    public function setStart($start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param string $end
     * @return DeliveryOptionTime
     */
    public function setEnd($end)
    {
        $this->end = $end;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return DeliveryOptionTime
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriceComment()
    {
        return $this->price_comment;
    }

    /**
     * @param string $price_comment
     * @return DeliveryOptionTime
     */
    public function setPriceComment($price_comment)
    {
        $this->price_comment = $price_comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return DeliveryOptionTime
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return DeliveryOptionTime
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
