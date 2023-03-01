<?php


namespace Samman\solr\traits;

use Samman\solr\helpers\SolrResponse;
use Samman\solr\SolrQuery;
use yii\helpers\ArrayHelper;
use yii\httpclient\Exception;
use yii\base\InvalidConfigException;
use Samman\solr\helpers\QueryHelper;
use Samman\solr\helpers\SolrNormalResponse;
use Samman\solr\helpers\SolrGroupedResponse;
use Samman\solr\helpers\ConditionalQueryBuilder;

trait SolrQueryTrait
{
    /**
     * @param $attributes
     * @return SolrQuery
     */
    public function select($attributes): SolrQuery
    {
        if (is_array($attributes)) {
            $attributes = implode(',', $attributes);
        }

        $this->fields = $attributes;
        return $this;
    }

    /**
     * @param array $condition
     * @return SolrQuery
     * @throws \Exception
     */
    public function where($condition): SolrQuery
    {
        $this->fq = ArrayHelper::merge($this->fq, ConditionalQueryBuilder::generate()
            ->condition($condition)
            ->operand('AND')
            ->build());

        return $this;
    }

    /**
     * @param array $condition
     * @return SolrQuery
     * @throws \Exception
     */
    public function andWhere($condition): SolrQuery
    {
        return $this->where($condition);
    }

    /**
     * @param array $condition
     * @return SolrQuery
     * @throws \Exception
     */
    public function orWhere($condition): SolrQuery
    {
        $this->fq = ArrayHelper::merge($this->fq, ConditionalQueryBuilder::generate()
            ->condition($condition)
            ->operand('OR')
            ->build());

        return $this;
    }

    /**
     * @param array $condition
     * @return SolrQuery
     * @throws \Exception
     */
    public function filterWhere($condition): SolrQuery
    {
        $this->fq = ArrayHelper::merge($this->fq, ConditionalQueryBuilder::generate()
            ->condition($condition)
            ->operand('AND')
            ->ignoreNull(true)
            ->build());

        return $this;
    }

    /**
     * @param array $condition
     * @return SolrQuery
     * @throws \Exception
     */
    public function andFilterWhere($condition): SolrQuery
    {
        return $this->filterWhere($condition);
    }

    /**
     * @param array $condition
     * @return SolrQuery
     * @throws \Exception
     */
    public function orFilterWhere($condition): SolrQuery
    {
        $this->fq = ArrayHelper::merge($this->fq, ConditionalQueryBuilder::generate()
            ->condition($condition)
            ->operand('OR')
            ->ignoreNull(true)
            ->build());

        return $this;
    }

    /**
     * @return SolrNormalResponse|SolrGroupedResponse
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function all($db = null): SolrResponse
    {
        return $this->send();
    }

    /**
     * @return SolrNormalResponse|SolrGroupedResponse
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function one($db = null): ?SolrResponse
    {
        $this->limit(1);
        return $this->send();
    }

    /**
     * @param string $q
     * @param null $db
     * @return int
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function count($q = '*', $db = null): int
    {
        $cloneModel = clone $this;
        $response = $cloneModel->send();
        return $response->totalCount;
    }

    /**
     * @param null $db
     * @return bool
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function exists($db = null): bool
    {
        $count = $this->count();
        return !empty($count);
    }

    /**
     * @param $column
     * @param int $limit
     * @return SolrQuery
     */
    public function indexBy($column, $limit = 20): SolrQuery
    {
        $this->group = true;
        $this->groupLimit = $limit;
        $this->groupField = $column;
        return $this;
    }

    /**
     * @param $limit
     * @return SolrQuery
     */
    public function limit($limit): SolrQuery
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param $offset
     * @return SolrQuery
     * @throws \Exception
     */
    public function offset($offset): SolrQuery
    {
        if (!is_numeric($offset)) {
            throw new \Exception("Offset should be a number.");
        }

        $this->offset = $offset;
        return $this;
    }

    /**
     * @param $value
     * @return SolrQuery
     * @deprecated
     */
    public function emulateExecution($value = true): SolrQuery
    {
        return $this;
    }

    /**
     * @param $columns
     * @return SolrQuery
     */
    public function orderBy($columns): SolrQuery
    {
        if (!is_array($columns)) {
            $columns = [$columns => SORT_ASC];
        }

        foreach ($columns as $orderAttribute => $sortDirection) {
            $formattedSortDirection = QueryHelper::formatSortOrder($sortDirection);
            $this->sort[] = "$orderAttribute $formattedSortDirection";
        }
        return $this;
    }

    /**
     * @param $columns
     * @return SolrQuery
     */
    public function addOrderBy($columns): SolrQuery
    {
        return $this->orderBy($columns);
    }
}