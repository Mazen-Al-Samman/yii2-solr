<?php

namespace Samman\solr;

use stdClass;
use Exception;
use yii\db\ActiveRecord;
use yii\db\QueryInterface;
use yii\httpclient\Client;
use yii\base\InvalidConfigException;
use Samman\solr\helpers\SolrResponse;
use Samman\solr\traits\SolrQueryTrait;
use Samman\solr\helpers\ConditionalQueryBuilder;

class SolrQuery implements QueryInterface
{
    use SolrQueryTrait;

    private $rows;
    private $fields;
    private $fq = [];
    private $sort = [];
    private $q = "*:*";
    private $limit = 20;
    private $groupLimit;
    private $modelClass;
    private $offset = 0;
    private $group = false;
    private $groupField = '';
    private ?SolrHelper $_solr;
    private $collectionName = '';
    private ?SolrResponse $_models = null;

    /**
     * @param $solrModel
     * @throws Exception
     */
    public function __construct($solrModel)
    {
        $this->_solr = $solrModel;
        if (empty($this->_solr)) throw new Exception("SOLR Model can't be empty.");

        $this->collectionName = $this->_solr->collection;
        if (empty($this->collectionName)) throw new Exception("Collection can't be empty.");
    }

    /**
     * @return null|SolrResponse
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    private function send(): ?SolrResponse
    {
        $queryParams = $this->prepareParams();
        $client = new Client(['baseUrl' => $this->_solr->getBaseUrl()]);
        $request = $client->createRequest()
            ->setData($queryParams)
            ->setMethod('GET')
            ->setUrl("$this->collectionName/select");

        $response = $request->send();
        $responseModel = SolrResponse::createInstance($response, $this);

        if ($response->isOk) {
            assert($responseModel instanceof SolrResponse);
            $this->_models = $responseModel->getResponseFormatterClass()->format();
            if (!empty($this->modelClass)) {
                $this->buildModels();
            }
            return $this->_models;
        }
        return null;
    }

    /**
     * This function will convert returned objects as a specific model.
     * @param $modelClass
     * @return $this
     * @throws Exception
     */
    public function asModels($modelClass): SolrQuery
    {
        $checkerModel = new $modelClass();
        if (!$checkerModel instanceof ActiveRecord) {
            throw new Exception("Model should be an instance of yii/db/ActiveRecord");
        }

        $this->modelClass = $modelClass;
        return $this;
    }

    /**
     * @return array
     */
    private function prepareParams(): array
    {
        $params = [
            'q' => $this->q,
            'fl' => $this->fields,
            'rows' => $this->limit,
            'fq' => $this->getParsedQuery(),
            'group.field' => $this->groupField,
            'group.limit' => $this->groupLimit,
            'group' => $this->group ? 'true' : 'false',
            'sort' => implode(',', $this->sort),
            'start' => $this->offset === -1 ? 0 : $this->offset,
        ];

        if ($params['rows'] === -1) unset($params['rows']);
        return $params;
    }

    /**
     * @return stdClass
     */
    public function attributes(): stdClass
    {
        $attributeObject = new stdClass();
        $attributeObject->group = $this->group;
        return $attributeObject;
    }

    /**
     * This will parse the FQ and convert it to a URL format.
     * @return string
     */
    private function getParsedQuery(): string
    {
        if (empty($this->fq) || !is_array($this->fq)) return '';
        $formattedAttributes = '';
        foreach ($this->fq as $item) {
            [$attribute, $value, $operand] = $item;
            $conditionSign = empty($item[ConditionalQueryBuilder::TYPE_NOT]) ? '' : '!';

            if (empty($formattedAttributes)) {
                $formattedAttributes = "$conditionSign$attribute:$value";
                continue;
            }

            $keyValuePair = "$operand $conditionSign$attribute:$value";
            $formattedAttributes = "($formattedAttributes $keyValuePair)";
        }
        return $formattedAttributes;
    }


    /**
     * @return SolrResponse|null
     */
    private function buildModels(): ?SolrResponse
    {
        $returnModels = [];
        $responseModels = $this->_models;
        if (isset($responseModels->models)) {
            $responseModels = $responseModels->models;
        }

        $namespaceComponents = explode('\\', $this->modelClass);
        $modelName = end($namespaceComponents);
        $modelPrimaryKey = strtolower($modelName) . "_id";

        foreach ($responseModels as $modelArray) {
            $returnModel = new $this->modelClass();
            assert($returnModel instanceof ActiveRecord);

            if (isset($modelArray[$modelPrimaryKey])) {
                $modelId = $modelArray[$modelPrimaryKey];
                $modelArray['id'] = $modelId;
                unset($modelArray[$modelPrimaryKey]);
            }

            $returnModel->setAttributes($modelArray, false);
            $returnModels[] = $returnModel;
        }
        $this->_models->models = $returnModels;
        return $this->_models;
    }
}