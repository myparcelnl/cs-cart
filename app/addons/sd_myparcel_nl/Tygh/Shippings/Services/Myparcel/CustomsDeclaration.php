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
 * Class CustomsDeclaration
 * Used in non-domestic delivery
 * @todo: remove this class if not really used
 *
 * @package Tygh\Shippings\Services\Myparcel
 */
class CustomsDeclaration
{
    /**
     * @var
     * Data type: package_contents
     * Required: Yes.
     * The type of contents in the package.
     */
    private $contents;

    /**
     * @var
     * Data type: string
     * Required: Yes for commercial goods, commercial samples and return shipment package contents.
     * The invoice number for the commercial goods or samples of package contents.
     */
    private $invoice;

    /**
     * @var
     * Data type: integer
     * Required: Yes.
     * The total weight for all items in whole grams.
     */
    private $weight;

    /**
     * @var
     * Data type: array of CustomsItem objects
     * Required: Yes.
     * An array containing CustomsItem objects with description for each item in the package.
     */
    private $items;

    /**
     * @return mixed
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param mixed $contents
     * @return CustomsDeclaration
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param mixed $invoice
     * @return CustomsDeclaration
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param mixed $weight
     * @return CustomsDeclaration
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param mixed $items
     * @return CustomsDeclaration
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

}
