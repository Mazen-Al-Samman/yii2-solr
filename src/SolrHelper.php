<?php

namespace Samman\solr;

use yii\db\Query;
use yii\helpers\Json;
use yii\helpers\Console;
use yii\httpclient\Client;
use yii\helpers\BaseConsole;
use yii\httpclient\Exception;
use yii\base\InvalidConfigException;
use Samman\solr\interfaces\SolrInterface;

class SolrHelper
{
    public $port = 8983;
    public $errorMessages;
    public $collection = '';
    public $hasError = false;
    public $schemaFilesPath = '/';
    public $url = 'http://localhost';

    // SOLR ACTIONS
    const ACTION_CREATE = 'CREATE';
    const ACTION_DELETE = 'DELETE';
    const ACTION_ADD_FIELD = 'add-field';

    /**
     * Get the SOLR query instance.
     * @param string $collectionName
     * @return SolrQuery
     * @throws \Exception
     */
    public function query($collectionName = ''): SolrQuery
    {
        if (!empty($collectionName)) $this->collection = $collectionName;
        return new SolrQuery($this);
    }

    /**
     * This will concatenate the url with the port to return the full URL.
     * @return string
     */
    public function getBaseUrl(): string
    {
        return "$this->url:$this->port/solr/";
    }

    /**
     * This will create a new SOLR collection
     * @return bool
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function createCollection(): bool
    {
        $requestBody = [
            'numShards' => 1,
            'replicationFactor' => 1,
            'name' => $this->collection,
            'action' => self::ACTION_CREATE,
        ];

        return $this->getClient($requestBody);
    }

    /**
     * This will Drop an existing SOLR collection
     * @return bool
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function dropCollection(): bool
    {
        $requestBody = [
            'action' => self::ACTION_DELETE,
            'name' => $this->collection,
        ];

        return $this->getClient($requestBody);
    }

    /**
     * @return bool
     * @throws Exception
     * @throws InvalidConfigException
     */
    public final function defineSchema(): bool
    {
        $filePath = $this->schemaFilesPath . "$this->collection.php";
        if (!file_exists($filePath)) {
            $this->addError("File ($filePath) does not exist!");
            return false;
        }

        /** @var array $collectionSchema */
        $collectionSchema = require $filePath;
        $requestContent = Json::encode([self::ACTION_ADD_FIELD => $collectionSchema]);

        $client = new Client(['baseUrl' => $this->getBaseUrl()]);
        $request = $client->createRequest()
            ->setMethod('POST')
            ->setContent($requestContent)
            ->setUrl("$this->collection/schema")
            ->addHeaders(['Content-Type' => 'application/json']);

        $response = $request->send();
        if ($response->isOk) return true;

        $this->addError($response->content);
        return false;
    }

    /**
     * @param Query $dataBaseQuery
     * @param int $batchSize
     * @return void
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function indexByQuery($dataBaseQuery, $batchSize = 100): void
    {
        $batchesCount = 0;
        $modelsCount = $dataBaseQuery->count();
        $totalBatches = ceil($modelsCount / $batchSize);
        Console::stderr(Console::ansiFormat(" START INDEXING MODELS " . PHP_EOL, [BaseConsole::BG_BLUE, BaseConsole::BOLD]));

        foreach ($dataBaseQuery->batch($batchSize) as $batch) {
            $modelsData = [];
            foreach ($batch as $model) {
                assert($model instanceof SolrInterface);
                $modelsData[] = self::prepareFields($model);
            }
            if ($this->indexBatch($modelsData)) {
                $batchesCount++;
                Console::stderr(Console::ansiFormat(" $batchesCount / $totalBatches DONE. " . PHP_EOL, [BaseConsole::BOLD, BaseConsole::FG_GREEN]));
            }
            sleep(2);
        }
    }

    /**
     * @param $batch
     * @return bool
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function indexBatch($batch): bool
    {
        $client = new Client(['baseUrl' => $this->getBaseUrl()]);
        $request = $client->createRequest()
            ->setMethod('POST')
            ->setContent(Json::encode($batch))
            ->addHeaders(['Accept' => 'application/json'])
            ->setUrl("$this->collection/update?commit=true")
            ->addHeaders(['Content-Type' => 'application/json']);

        $response = $request->send();
        if ($response->isOk) return true;

        $this->addError($response->content);
        return false;
    }

    /**
     * @param SolrInterface $model
     * @return array
     */
    private function prepareFields($model): array
    {
        $fields = $model->solrFields();
        return array_map(function ($value) use ($model) {
            // Call the Closure function.
            if (is_object($value)) return $value();
            return $model->{"$value"};
        }, $fields);
    }

    /**
     * This will collect errors
     * @param $errorMessage
     * @return void
     */
    private function addError($errorMessage): void
    {
        $this->hasError = true;
        $this->errorMessages[] = $errorMessage;
    }

    /**
     * @param array $requestBody
     * @return bool
     * @throws Exception
     * @throws InvalidConfigException
     */
    private function getClient(array $requestBody): bool
    {
        $client = new Client(['baseUrl' => $this->getBaseUrl()]);
        $request = $client->createRequest()
            ->setUrl('admin/collections')
            ->addHeaders(['Content-Type' => 'application/json'])
            ->setData($requestBody);

        $response = $request->send();
        if ($response->isOk) return true;

        $this->addError($response->content);
        return false;
    }
}