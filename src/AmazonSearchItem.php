<?php

/**
 * @link https://github.com/Bebup/yii2-paapi
 * @copyright Copyright (c) 2020 Bebup
 * @license https://opensource.org/licenses/LGPL-3.0
 */

namespace bebup\amazonpaapi;

use Amazon\ProductAdvertisingAPI\v1\ApiException;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\Item;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResource;
use yii\base\InvalidCallException;

/**
 * @author gpayo
 */
class AmazonSearchItem extends AmazonPAAPI {
    /**
     * Searches Amazon DB in the index specified and with the keyword given
     *
     * For Indexes see:
     * @link https://webservices.amazon.com/paapi5/documentation/use-cases/organization-of-items-on-amazon/search-index.html
     *
     * For Resources (check constants defined in SearchItemsResource
     * @link https://webservices.amazon.com/paapi5/documentation/search-items.html#resources-parameter
     * @see SearchItemsResource
     *
     * For Extras see:
     * @see SearchItemsRequest::__construct()
     *
     * @param string $index      The Amazon index to use
     * @param string $keyword    The keyword to search for
     * @param int    $item_count The amount of items to return
     * @param array  $resources  An array of resources
     * @param array  $extra      The rest of possible SearchItemsRequest's contants
     * @return Item[]
     * @throws InvalidCallException
     */
    public function getItems(string $index, ?string $keyword = '', int $item_count = 10, array $resources = [], array $extra = []): array {
        $data = array_merge($extra, [
            'searchIndex' => $index,
            'keywords' => $keyword,
            'itemCount' => $item_count,
            'resources' => $resources,
            'partnerTag' => $this->partner_tag,
            'partnerType' => PartnerType::ASSOCIATES,
        ]);
        $search_item_request = $this->getSearchItemRequest($data);

        try {
            $search_result = $this
                ->getApiInstance()
                ->searchItems($search_item_request)
                ->getSearchResult();
            if (!$search_result) {
                return [];
            }
            return (array)$search_result->getItems();
        } catch (ApiException $exception) {
            throw new InvalidCallException($this->getInvalidCallExceptionMessage($exception));
        } catch (\Exception $exception) {
            throw new InvalidCallException($exception->getMessage());
        }

        return [];
    }

    /**
     * Returns a correctly populated SearchItemsRequest
     *
     * @param array $data
     * @return SearchItemsRequest
     */
    protected function getSearchItemRequest(array $data): SearchItemsRequest {
        $search_item_request = new SearchItemsRequest($data);
        $this->checkForInvalidProperties($search_item_request);

        return $search_item_request;
    }
}
