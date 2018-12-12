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

namespace Tygh\Shippings\Services\Myparcel;

/**
 * Class Label
 * Describe the label data type
 *
 * @package Tygh\Shippings\Services\Myparcel
 */
class Label
{
    const FORMAT_A4 = 'A4',
          FORMAT_A6 = 'A6';

    const POSITION_TOP_LEFT = 1,
          POSITION_TOP_RIGHT = 2,
          POSITION_BOTTOM_LEFT = 3,
          POSITION_BOTTOM_RIGHT = 4;

    public static function getAllFormats()
    {
        return [
            'A4' => self::FORMAT_A4,
            'A6' => self::FORMAT_A6,
        ];
    }

    public static function getAllPositions()
    {
        return [
            'top-left' => self::POSITION_TOP_LEFT,
            'top-right' => self::POSITION_TOP_RIGHT,
            'bottom-left' => self::POSITION_BOTTOM_LEFT,
            'bottom-right' => self::POSITION_BOTTOM_RIGHT,
        ];
    }
}
