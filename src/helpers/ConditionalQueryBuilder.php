<?php

namespace Samman\solr\helpers;

use Exception;
use yii\helpers\ArrayHelper;

class ConditionalQueryBuilder
{
    private array $condition = [];
    private string $operand = 'AND';
    private bool $ignoreNull = false;

    const TYPE_NOT = 'not';
    const TYPE_LIKE = 'like';

    /**
     * @return ConditionalQueryBuilder
     */
    public static function generate(): ConditionalQueryBuilder
    {
        return new self();
    }

    /**
     * @param array $condition
     * @return ConditionalQueryBuilder
     */
    public function condition(array $condition): ConditionalQueryBuilder
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * @param string $operand
     * @return ConditionalQueryBuilder
     */
    public function operand(string $operand): ConditionalQueryBuilder
    {
        $this->operand = $operand;
        return $this;
    }

    /**
     * @param bool $ignoreNull
     * @return ConditionalQueryBuilder
     */
    public function ignoreNull(bool $ignoreNull): ConditionalQueryBuilder
    {
        $this->ignoreNull = $ignoreNull;
        return $this;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function build(): array
    {
        if (empty($this->condition)) {
            throw new Exception("Condition can't be blank");
        }

        if (ArrayHelper::isAssociative($this->condition)) {
            return $this->buildAssociativeCondition();
        }

        return $this->buildSequentialArray();
    }

    /**
     * @return array
     */
    private function buildAssociativeCondition(): array
    {
        $conditionsArray = [];
        foreach ($this->condition as $attribute => $value) {
            if ($this->ignoreNull && is_null($value)) continue;
            $conditionsArray[] = [$attribute, $value, $this->operand];
        }
        return $conditionsArray;
    }

    /**
     * @return array
     */
    private function buildSequentialArray(): array
    {
        $conditionType = array_shift($this->condition);
        switch ($conditionType) {
            case self::TYPE_NOT:
                return $this->buildNotCondition();
            case self::TYPE_LIKE:
                return $this->buildLikeCondition();
            default:
                return [];
        }
    }

    /**
     * @return array
     */
    private function buildNotCondition(): array
    {
        [$attribute, $value] = $this->condition;
        return [$attribute, $value];
    }

    /**
     * @return array
     */
    private function buildLikeCondition(): array
    {
        [$attribute, $value] = $this->condition;
        $value = preg_replace('/%/', '', $value);
        return [[$attribute, $value]];
    }
}