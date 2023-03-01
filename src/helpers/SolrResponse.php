<?php

namespace Samman\solr\helpers;

use samman\Solr\SolrQuery;

class SolrResponse
{
    public int $totalCount;
    public array $models = [];
    protected object $_response;
    protected ?SolrQuery $query = null;

    private static ?self $_instance = null;
    private static array $FORMATTER_MAPPING = [];

    private function __construct()
    {
    }

    /**
     * @param object $response
     * @param SolrQuery $query
     * @return SolrResponse
     */
    public static function createInstance(object $response, SolrQuery $query): SolrResponse
    {
        $model = self::$_instance;
        if (empty($model) || (!$model instanceof SolrResponse)) $model = new self();
        $model->_response = $response;
        $model->query = $query;

        $queryAttributes = $model->query->attributes();
        self::$FORMATTER_MAPPING = [
            [$queryAttributes->group, SolrGroupedResponse::class],
        ];
        return $model;
    }

    /**
     * This will detect which Solr Response should be used based on the Map built on the constructor.
     * @return SolrResponse
     */
    public function getResponseFormatterClass(): SolrResponse
    {
        $responseModel = new SolrNormalResponse();
        foreach (self::$FORMATTER_MAPPING as $mappingItem) {
            [$value, $formatter] = $mappingItem;

            if ($value) {
                $responseModel = new $formatter();
                break;
            }
        }
        $responseModel->_response = $this->_response;
        return $responseModel;
    }
}