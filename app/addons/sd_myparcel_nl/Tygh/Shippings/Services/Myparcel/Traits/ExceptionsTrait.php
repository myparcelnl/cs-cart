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

/**
 * Trait ExceptionsTrait
 * Contained throws methods that used by different classes
 *
 * @package Tygh\Shippings\Services\Myparcel\Traits
 */
trait ExceptionsTrait
{
    private function throwConstructorParamsException()
    {
        throw new \Exception('Invalid parameters: ' . __FILE__ . ':' . __LINE__);
    }
}
