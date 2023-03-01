<?php

namespace Samman\solr;

use yii\data\ActiveDataProvider;
use yii\base\InvalidConfigException;

class SolrDataProvider extends ActiveDataProvider
{
    private array $_keys;
    private array $_models;

    /**
     * @param $forcePrepare
     * @return array|void
     * @throws InvalidConfigException
     */
    public function prepare($forcePrepare = false): array
    {
        $models = $this->prepareModels();
        if (isset($models->models)) {
            $models = $models->models;
        }

        $this->_models = $models;
        return $models;
    }

    /**
     * @return array
     */
    public function getKeys(): array
    {
        $this->_keys = $this->prepareKeys($this->_models);
        return $this->_keys;
    }

    /**
     * @param $models
     * @return array
     */
    protected function prepareKeys($models): array
    {
        if (isset($models->models)) {
            $models = $models->models;
        }

        return array_keys($models);
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function getModels(): array
    {
        $this->prepare();
        return $this->_models;
    }
}