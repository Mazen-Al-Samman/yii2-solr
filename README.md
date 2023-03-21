<h1 align="center">
    Yii2 SOLR
</h1>

<p align="center">
    Yii2 Package for SOLR active query.
</p>

![Yii2 Framework](https://img.shields.io/badge/Yii2-Framework-red.svg)


### BASIC FEATURES
* Create new collection
* Drop existing collection
* Define the collection schema
* Index the collection data
* Query to SOLR based on Yii2 Active Query syntax

---
### INSTALLATION

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
php composer.phar require samman/yii2-solr
```

or add

```json
"samman/yii2-solr": "*"
```

to the require section of your `composer.json` file.
<br>
<br>

In your `main-local.php` you should add the following

```php
'solr' => [
   'class' => \Samman\solr\SolrHelper::class,
   'collection' => '{collectionName}',
   'port' => 8983,
   'url' => 'http://localhost',
   'schemaFilesPath' => 'path/to/schema/'
]
```

To define the collection schema, create a new file in your `schema path` mentioned in the main local
with, and the file name will be `{collectionName}.php`

Replce the `{collectionName}` with you collection name mentioned in `main-local.php`

Your Schema file should be as the following
```php
<?php
return [
    ['name' => 'book_name', 'type' => 'text_general', 'multiValued' => false, 'indexed' => true, 'stored' => true],
    ['name' => 'book_author', 'type' => 'text_general', 'multiValued' => false, 'indexed' => true, 'stored' => true],
    ['name' => 'ISBN', 'type' => 'text_general', 'multiValued' => false, 'indexed' => true, 'stored' => true],
    ['name' => 'quantity', 'type' => 'pint', 'stored' => true],
    ['name' => 'price', 'type' => 'pfloat', 'multiValued' => false, 'indexed' => true, "stored" => "true"],
];
```

---

### GETTING STARTED

#### Link SOLR to your Model
In your model you should implement `Samman\solr\interfaces\SolrInterface` which have
an abstract function for SOLR fields, Here's an example

```php
public function solrFields(): array
{
    return ArrayHelper::merge($this->fields(), [
        'book_author_name' => function () {
            return $this->author->id;
        },
        'stock' => function () {
            return $this->quantity > 0;
        }
    ]);
}
```

This can be used to customize the collection fields.

#### Deal with Collections
You can use the below functions to work with SOLR collections.

```php
Yii::$app->solr->createCollection(); // Create a new collection
Yii::$app->solr->dropCollection(); // Drop existing collection
Yii::$app->solr->defineSchema(); // Define the collection schema
```

To add your data to the SOLR collection, you can run:
```php
$query = Books::find()->where(['available' => 1]);
Yii::$app->solr->indexByQuery($query);
```

Or you can index the array data as follows
```php
Yii::$app->solr->indexByArray($array);
```

#### SOLR Query
SOLR Query is built based on `Yii QueryInterface`, Here's an example

```php
$query = Yii::$app->solr->find()
        ->where(['ISBN' => '356743423'])
        ->andWhere(['book_name' => 'Yii2'])
        ->orWhere(['like', 'author_name', '%samman'])
        ->limit(10)
        ->offset(2)
        ->indexBy('id')
        ->orderBy(['id' => SORT_DESC])
        ->all();
```

The above query will return all documents which match the query.

#### CUSTOM FEATURES
* You can set a model for retrieved data, you can pass `asModel` function in your query.

```php
$query->asModel(Book::class)->all();
```

* You can use the `Samman\solr\SolrDataProvider` if you want to return the data as a dataProvider.

```php
$dataProvider = new \Samman\solr\SolrDataProvider([
    'query' => Yii::$app->solr->find()->all(),
]);
```