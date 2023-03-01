<?php

namespace Samman\solr\helpers;

class SolrNormalResponse extends SolrResponse
{
    /**
     * @param $response
     * @return SolrNormalResponse
     */
    public function setResponse($response): SolrNormalResponse
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * @return $this
     */
    public function format(): SolrNormalResponse
    {
        $responseArray = $this->_response->data;
        $responseResult = $responseArray['response'] ?? [];

        $this->models = $responseResult['docs'] ?? 0;
        $this->totalCount = $responseResult['numFound'] ?? 0;
        return $this;
    }
}