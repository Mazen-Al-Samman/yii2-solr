<?php

namespace Samman\solr\helpers;

use stdClass;

class SolrGroupedResponse extends SolrResponse
{
    /**
     * @param $response
     * @return SolrGroupedResponse
     */
    public function setResponse($response): SolrGroupedResponse
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * @return $this
     */
    public function format(): SolrGroupedResponse
    {
        $responseArray = $this->_response->data;
        $responseResult = $responseArray['grouped'] ?? [];

        foreach ($responseResult as $field => $result) {
            $groups = $result['groups'];
            $this->totalCount = $result['matches'] ?? 0;
            foreach ($groups as $group) {
                $value = $group['groupValue'];
                $docs = $group['doclist']['docs'] ?? [];
                $totalCount = $group['doclist']['numFound'] ?? 0;

                $model = new stdClass();
                $model->models = $docs ?? [];
                $model->totalCount = $totalCount ?? 0;
                $this->models[$field][$value] = $model;
            }
        }
        return $this;
    }
}