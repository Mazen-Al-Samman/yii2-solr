<?php

namespace Samman\solr\helpers;

class QueryHelper
{
    /**
     * @param string $order
     * @return string
     */
    public static function formatSortOrder(string $order): string
    {
        if (!is_numeric($order)) return $order;
        return (int)$order === SORT_ASC ? 'asc' : 'desc';
    }
}