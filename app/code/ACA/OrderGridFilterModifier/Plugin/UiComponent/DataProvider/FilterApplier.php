<?php

namespace ACA\OrderGridFilterModifier\Plugin\UiComponent\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class FilterApplier
 *
 * @package ACA\OrderGridFilterModifier\Plugin\UiComponent\DataProvider
 */
class FilterApplier
{
    /**
     * Name space of the sales order grid that passes to the mui render call.
     */
    const SALES_ORDER_GRID_NAMESPACE = 'sales_order_grid';

    /**
     * @var Http
     */
    protected $request;

    /**
     * FilterApplier constructor.
     *
     * @param Http $request
     * @return void
     */
    public function __construct(
        Http $request
    ) {
        $this->request = $request;
    }

    /**
     * This plugin will be executed before a filter is applied to the collection.
     * So each time a filter is applied in the sales order grid, we will check and if it is an ID filter
     * then we will change the condition of that filter from 'like' to 'in'. Also in the value there will be
     * %% symbols appended in the beginning and end of the value, we will remove that also.
     *
     * @param FilterApplierInterface $subject
     * @param Collection $collection
     * @param Filter $filter
     * @return array
     */
    public function beforeApply(FilterApplierInterface $subject, Collection $collection, Filter $filter)
    {
        // Get the namespace parameter from the request.
        // When we filter the grid, the request url will be mui/index/render.
        $namespace = $this->request->getParam('namespace');
        // Check it is sales_order_grid
        if ($namespace == self::SALES_ORDER_GRID_NAMESPACE) {
            // Out custom changes only applicable to the ID filter.
            if ($filter->getField() == OrderInterface::INCREMENT_ID) {
                // There will be % symbol in the start and end of the value. This is because the original
                // condition is like and for this they are preparing like this. We need to remove that.
                $modifiedFilterValue = str_replace('%', '', $filter->getValue());
                // Remove all the spaces in the comma separated string of id's
                $modifiedFilterValue = preg_replace('/\s+/', '', $modifiedFilterValue);
                // This will enable the feature to filter the order ids without the leading zeros in the id.
                // Here we are checking if the searching string is start with a 00, then normal filter will work on.
                // Else if the ids is not start with a 00 then we will remove the leading zeros from the increment_id
                // before the search in mysql. So in the grid we can now filter the id with leading zero and
                // non leading zero ids. We will modify the query based on the type of the string coming in the filter.
                if (strncmp($modifiedFilterValue, '00', 2) != 0) {
                    $filter->setField(new \Zend_Db_Expr('TRIM(Leading \'0\' from increment_id)'));
                }
                $filter->setValue($modifiedFilterValue);
                // Change the condition to IN operator
                $filter->setConditionType('in');
            }
        }

        return [$collection, $filter];
    }
}
