<?php

/**
 * @link https://github.com/Bebup/yii2-paapi
 * @copyright Copyright (c) 2020 Bebup
 * @license https://opensource.org/licenses/LGPL-3.0
 */

namespace bebup\amazonpaapi;

use Amazon\ProductAdvertisingAPI\v1\ApiException;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\Item;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use yii\base\InvalidCallException;

/**
 *
 *
 * @author gpayo
 */
class GetItems extends AmazonPAAPI {
    /**
     * Returns the Amazon items by the given id
     *
     * For Extras see:
     * @see GetItemsRequest::__construct()
     *
     * @param string|string[] $amazon_ids A string or array of string with the Amazon ids to return
     * @param array           $extra      An array of extra configs
     * @return Item[]
     * @throws InvalidCallException
     */
    public function getItems($amazon_ids, array $extra = []): array {
        $ids = (array)$amazon_ids;
        $data = array_merge($extra, [
            'itemIds' => $ids,
            'partnerTag' => $this->partner_tag,
            'partnerType' => PartnerType::ASSOCIATES,
        ]);
        $get_item_request = $this->getItemRequest($data);
        try {
            $get_items_response = $this->getApiInstance()->getItems($get_item_request);
            $item_result = $get_items_response->getItemsResult();
            return $item_result->getItems();
        } catch (ApiException $exception) {
            throw new InvalidCallException($this->getInvalidCallExceptionMessage($exception));
        } catch (\Exception $exception) {
            throw new InvalidCallException($exception->getMessage());
        }

        return [];
    }

    /**
     * Returns a correctly populated GetItemsRequest
     *
     * @param array $data
     * @return GetItemsRequest
     */
    protected function getItemRequest(array $data): GetItemsRequest {
        $get_items_request = new GetItemsRequest($data);
        $this->checkForInvalidProperties($get_items_request);

        return $get_items_request;
    }
}
