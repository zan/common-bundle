<?php

namespace Zan\CommonBundle\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

class ZanSql
{
    /**
     * @param $con \Doctrine\DBAL\Connection
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public static function toArray($con, $query, $params = array())
    {
        $r = self::query($con, $query, $params);
        $rows = array();

        while ($row = $r->fetch()) {
            $rows[] = $row;
        }

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
     * @param       $con
     * @param       $query
     * @param array $params
     * @return array
     */
    public static function toFlatArray($con, $query, $params = array())
    {
        $r = self::query($con, $query, $params);
        $results = array();

        while ($row = $r->fetch()) {
            $results[] = array_shift($row);
        }

        return $results;
    }

    /**
     * @param $con \Doctrine\DBAL\Connection
     * @param string $query
     * @param array $params
     * @return mixed|null
     */
    public static function singleValue($con, $query, $params = array())
    {
        $r = self::query($con, $query, $params);
        $rows = [];

        while ($row = $r->fetch()) {
            $rows[] = $row;
        }

        if (count($rows) == 0) return null;

        $row = $rows[0];
        foreach ($row as $key => $value) {
            return $value;
        }

        return null;
    }

    /**
     * Returns the first result as an array of columns -> values
     *
     * @param       $con
     * @param       $query
     * @param array $params
     * @return array
     */
    public static function singleRow($con, $query, $params = array())
    {
        $r = self::query($con, $query, $params);

        while ($row = $r->fetch()) {
            return $row;
        }

        return [];
    }

    /**
     * @param $con \Doctrine\DBAL\Connection
     * @param string $query
     * @param array $params
     * @return Statement
     */
    public static function query($con, $query, $params = array())
    {
        $stmt = $con->prepare($query);
        $stmt->execute($params);

        return $stmt;
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
     * @param        $value
     * @param string $prefix
     * @param string $postfix
     * @return string
     */
    public static function escapeLikeParameter($value, $prefix = '%', $postfix = '%')
    {
        // ensure special characters % and _ are escaped
        $escapedValue = addcslashes($value, '%_');

        return sprintf('%s%s%s', $prefix, $escapedValue, $postfix);
    }

    /**
     * Returns a string describing the version of the database software.
     *
     * If a version cannot be determined this method returns null.
     *
     * @param $con \Doctrine\DBAL\Connection
     * @return string|null
     */
    public static function getServerVersion($con)
    {
        // MySQL
        if (ZanString::startsWithi('mysql', $con->getDatabasePlatform()->getName())) {
            return self::singleValue($con, 'select @@version');
        }

        // Unsupported database
        return null;
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
     * @param $fields
     * @return array
     */
    public static function buildSetInfoFromMap($fields, $extraQueryParts = [])
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
     * @param            $array
     * @param Connection $con
     * @return string
     */
    public static function inFromArray($array, Connection $con)
    {
        $inParts = [];

        foreach ($array as $elem) {
            $inParts[] = $con->quote($elem);
        }

        return join(', ', $inParts);
    }

    /**
     * Returns true if a table with name $needle exists in the database represented
     * by $con
     *
     * NOTE: Assumes that the first element returned by getListTablesSQL is the
     *  table name. This may not be cross platform!
     *
     * @param Connection $con
     * @param            $needle
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function tableExists(Connection $con, $needle)
    {
        $tables = $con->fetchAll($con->getDatabasePlatform()->getListTablesSQL());

        $matches = array_filter($tables, function($tableArr) use ($needle) {
            /*
             * $tableArr has keys like:
             *  Tables_in_<database name>
             *  Table_type
             *
             * Since <database name> is dynamic, use array_shift to grab the first element
             */
            $currTableName = array_shift($tableArr);
            return $currTableName === $needle;
        });

        return count($matches) != 0;
    }
}