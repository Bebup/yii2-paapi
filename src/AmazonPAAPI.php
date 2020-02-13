<?php

/**
 * @link https://github.com/Bebup/yii2-paapi
 * @copyright Copyright (c) 2020 Bebup
 * @license https://opensource.org/licenses/LGPL-3.0
 */

namespace bebup\amazonpaapi;

use Amazon\ProductAdvertisingAPI\v1\ApiException;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\api\DefaultApi;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\Item;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\ProductAdvertisingAPIClientException;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResource;
use Amazon\ProductAdvertisingAPI\v1\Configuration;
use GuzzleHttp\Client;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;

/**
 *
 *
 * @author gpayo
 */
class AmazonPAAPI extends Component {
    /**
     * Amazon's access key for the API
     *
     * @var string
     */
    public $access_key;

    /**
     * Amazon's secret key for the API
     *
     * @var string
     */
    public $secret_key;

    /**
     * The partner tag
     *
     * @var string
     */
    public $partner_tag;

    /**
     * PAAPI Host
     *
     * @link https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
     * @var string
     */
    public $host;

    /**
     * PAAPI Region
     *
     * @link https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
     * @var string
     */
    public $region;

    /**
     * Singleton instance of the Amazon's API connector
     *
     * @var DefaultApi|null
     */
    protected static $api_instance;

    /**
     * Checks if all the required parameters are ok
     *
     * @return void
     */
    public function init() {
        $this->exceptionIfEmpty($this->access_key);
        $this->exceptionIfEmpty($this->secret_key);
        $this->exceptionIfEmpty($this->partner_tag);
        $this->exceptionIfEmpty($this->host);
        $this->exceptionIfEmpty($this->region);
    }

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
    public function searchItems(string $index, string $keyword = '', int $item_count = 10, array $resources = [], array $extra = []): array {
        $data = array_merge($extra, [
            'searchIndex' => $index,
            'keywords' => $keyword,
            'itemCount' => $item_count,
            'resources' => $resources,
        ]);
        $search_item_request = $this->getSearchItemRequest($data);

        try {
            $search_items_response = $this->getApiInstance()->searchItems($search_item_request);
            $item_result = $search_items_response->getSearchResult();
            return $item_result->getItems();
        } catch (ApiException $exception) {
            throw new InvalidCallException($this->getInvalidCallExceptionMessage($exception));
        } catch (\Exception $exception) {
            throw new InvalidCallException($exception->getMessage());
        }

        return [];
    }

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
     * Formats an error message with the given ApiException object
     *
     * @param ApiException $exception
     * @return string
     */
    protected function getInvalidCallExceptionMessage(ApiException $exception): string {
        $a_message = [];
        $a_message[] = "Error calling PA-API 5.0!";
        $a_message[] = sprintf("HTTP Status Code: %s", $exception->getCode());
        $a_message[] = sprintf("Error Message: %s", $exception->getMessage());
        $a_message[] = '';
        if ($exception->getResponseObject() instanceof ProductAdvertisingAPIClientException) {
            $errors = $exception->getResponseObject()->getErrors();
            foreach ($errors as $error) {
                $a_message[] = sprintf("Error Type: %s", $error->getCode());
                $a_message[] = sprintf("Error Message: %s", $error->getMessage());
            }
            return implode("\n", $a_message);
        }

        return sprintf("Error response body: %s", $exception->getResponseBody());
    }

    /**
     * Checks for invalid properties and throws an exception
     * if there is one found
     *
     * @param SearchItemsRequest $search_item_search
     * @return bool
     * @throws InvalidConfigException
     */
    protected function checkForInvalidProperties(SearchItemsRequest $search_item_search): bool {
        $invalid_property_list = $search_item_search->listInvalidProperties();
        $length = count($invalid_property_list);
        $message = [];
        if ($length > 0) {
            $message[] = 'Invalid properties:';
            foreach ($invalid_property_list as $invalid_property) {
                $message[] = $invalid_property;
            }
            throw new InvalidConfigException(implode("\n", $message));
        }

        return true;
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

    /**
     * Gets the already created PA API to query Amazon
     *
     * @return DefaultApi
     */
    protected function getApiInstance(): DefaultApi {
        if (!$this::$api_instance) {
            $this::$api_instance = new DefaultApi(new Client(), $this->getConfig());
        }
        return $this::$api_instance;
    }

    /**
     * Builds a configuration to be used for the API instance
     *
     * @return Configuration
     */
    protected function getConfig(): Configuration {
        $config = new Configuration();
        $config->setAccessKey($this->access_key);
        $config->setSecretKey($this->secret_key);
        $config->setHost($this->host);
        $config->setRegion($this->region);
        return $config;
    }

    /**
     * Checks whether $val is empty, if so it throws an exception
     *
     * @param mixed $val
     * @return bool
     * @throws InvalidConfigException
     */
    protected function exceptionIfEmpty($val): bool {
        if (empty($val)) {
            throw new InvalidConfigException();
        }

        return true;
    }
}
