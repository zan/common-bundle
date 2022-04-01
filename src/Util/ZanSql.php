<?php

namespace Zan\CommonBundle\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;

class ZanSql
{
    /**
     * Executes $query and returns an array of row data
     *
     * @param array<string,mixed> $params
     * @return array<array<string,string>>
     */
    public static function toArray(Connection $con, string $query, array $params = []): array
    {
        $r = self::query($con, $query, $params);
        $rows = [];

        while ($row = $r->fetchAssociative()) {
            $rows[] = $row;
        }

        /** @phpstan-ignore-next-line Method Zan\CommonBundle\Util\ZanSql::toArray() should return array<array<string, string>> but returns array<int, mixed>. */
        return $rows;
    }

    /**
     * Returns an array of the first column in the returned rows. For example, if
     * the query would return the following rows:
     *
     * Name         Age
     * -----        -----
     * Jim          12
     * Bob          20
     *
     * This method would return array('Jim', 'Bob')
     *
     * @param array<string,mixed> $params
     * @return array<array<string,string>>
     */
    public static function toFlatArray(Connection $con, string $query, array $params = []): array
    {
        $r = self::query($con, $query, $params);
        $results = array();

        while ($row = $r->fetchAssociative()) {
            $results[] = array_shift($row);
        }

        /** @phpstan-ignore-next-line Method Zan\CommonBundle\Util\ZanSql::toFlatArray() should return array<string> but returns array<int, mixed>. */
        return $results;
    }

    /**
     * Returns the first column of the first row after executing $query
     *
     * @param array<string|int,mixed> $params
     */
    public static function singleValue(Connection $con, string $query, array $params = []): ?string
    {
        $r = self::query($con, $query, $params);
        $rows = [];

        while ($row = $r->fetchAssociative()) {
            $rows[] = $row;
        }

        if (count($rows) == 0) return null;

        /** @var array<string> $columns */
        $columns = $rows[0];
        if (count($columns) == 0) return null;

        return array_shift($columns);
    }

    /**
     * Returns the first result as an array of columns -> values
     *
     * @param array<string,mixed> $params
     * @return array<string>
     */
    public static function singleRow(Connection $con, string $query, array $params = []): array
    {
        $r = self::query($con, $query, $params);

        while ($row = $r->fetchAssociative()) {
            /* @phpstan-ignore-next-line Method Zan\CommonBundle\Util\ZanSql::singleRow() should return array but returns mixed. */
            return $row;
        }

        return [];
    }

    /**
     * @param array<string,mixed> $params
     */
    public static function query(Connection $con, string $query, array $params = []): Result
    {
        $stmt = $con->prepare($query);

        return $stmt->executeQuery($params);
    }

    /**
     * Escapes the value used in a LIKE query.
     *
     * This method ensures that characters with special meanings in LIKE queries
     * are correctly escaped.
     *
     * The default values for $prefix and $postfix result in the value being
     * allowed to appear anywhere in the string. To query for records that start
     * with $value, set $prefix to an empty string.
     *
     * Example:
     *
     *      if (!empty($query)) {
                $qb->where('r.label LIKE :query')
                ->setParameter('query', ZanSql::escapeLikeParameter($query));
            }
     *
     */
    public static function escapeLikeParameter(string $value, string $prefix = '%', string $postfix = '%'): string
    {
        // ensure special characters % and _ are escaped
        $escapedValue = addcslashes($value, '%_');

        return sprintf('%s%s%s', $prefix, $escapedValue, $postfix);
    }

    /**
     * Utility method for generating a "set" query and associated parameters.
     *
     * Usage example:
     *
     * Starting with an array of data representing a row in the database
     *  $row = [ 'requesterUid' => 100, 'comments' => 'some comments' ];
     *
     * The goal is to generate a query like:
     *  insert into orders set requesterUid = ?, comments = ?
     * With parameters
     *  100, 'some comments'
     *
     * To accomplish this, call this method:
     *  $setInfo = ZanSql::buildSetInfoFromMap($row);
     *
     * The return values will be:
     *  query: insert into orders set requesterUid = :setValue_requesterUid, comments = :setValue_comments
     *  parameters: [ 'setValue_requesterUid' => 100, 'setValue_comments' => 'some comments' ]
     *
     *
     * These can then be used in a generated query like:
            $setInfo = ZanSql::buildSetInfoFromMap($row);
            $sql = "insert into orders set " . $setInfo['query'];
            ZanSql::query($con, $sql, $setInfo['parameters']);
     *
     *
     * @param array<string, mixed> $fields
     * @param array<string> $extraQueryParts
     * @return array<string, mixed>
     */
    public static function buildSetInfoFromMap(array $fields, array $extraQueryParts = []): array
    {
        $setInfo = [
            'query' => '',
            'parameters' => []
        ];

        $queryParts = $extraQueryParts;
        foreach ($fields as $name => $value) {
            $queryParts[] = sprintf('%s = :setField_%s', $name, $name);
            $setInfo['parameters']['setField_' . $name] = $value;
        }

        $setInfo['query'] = join(', ', $queryParts);

        return $setInfo;
    }

    /**
     * Returns the inner parts (between the parentheses) for an "IN" query
     *
     * Example:
     *  $inValues = ZanSql::inFromArray($allParentContainerIds, $this->getEntityManager()->getConnection())
     *  $sql = "select * from customers where id IN ($inValues)"
     *
     * NOTE that this method handles escaping the individual elements so you should
     *  not do additional escaping or bind them to a parameter.
     *
     * @param array<string> $array
     */
    public static function inFromArray(array $array, Connection $con): string
    {
        $inParts = [];

        foreach ($array as $elem) {
            $inParts[] = $con->quote($elem);
        }

        return join(', ', $inParts);
    }
}
