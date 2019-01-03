<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/12/11
 * Time: 0:05
 */

namespace SeasStash\Clickhouse;

use SeasStash\Clickhouse\Query\Degeneration\Bindings;
use SeasStash\Clickhouse\Query\WhereInFile;
use SeasStash\Clickhouse\Query\WriteToFile;
use SeasStash\Clickhouse\Quote\FormatLine;
use Swlib\Saber;

/**
 * Class Client
 * @package Clickhouse
 */
class Clickhouse
{
    /** @var Http */
    private $http;

    /**
     * Client constructor.
     * @param array $options
     */
    public function __construct(Http $http, string $database = 'default')
    {
        $http->addQueryDegeneration(new Bindings());
        $http->settings()->database($database);
        $this->http = $http;
    }

    /**
     * @return Saber
     */
    public function getClient(): Saber
    {
        return $this->http;
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @param WhereInFile|null $whereInFile
     * @param WriteToFile|null $writeToFile
     * @return Saber\Response
     */
    public function select(
        string $sql,
        array $bindings = [],
        WhereInFile $whereInFile = null,
        WriteToFile $writeToFile = null
    ): Saber\Response
    {
        return $this->http->select($sql, $bindings, $whereInFile, $writeToFile);
    }

    /**
     * @param string $table
     * @param array $values
     * @param array $columns
     * @return Saber\Response
     */
    public function insert(string $table, array $values): Saber\Response
    {
        if (stripos($table, '`') === false && stripos($table, '.') === false) {
            $table = '`' . $table . '`'; //quote table name for dot names
        }

        $sql = 'INSERT INTO ' . $table;
        if (is_string(key($values))) {
            $values = [$values];
        }
        $columns = array_keys($values[0]);

        if (count($columns) !== 0) {
            $sql .= ' (`' . implode('`,`', $columns) . '`) ';
        }

        $sql .= ' VALUES ';

        foreach ($values as $data) {
            $row = [];
            foreach ($data as $column => $value) {
                $row[] = $value;
            }
            $sql .= ' (' . FormatLine::Insert($row) . '), ';
        }
        $sql = trim($sql, ', ');
        return $this->http->write($sql);
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @param bool $exception
     * @return Saber\Response
     */
    public function execute(string $sql, array $bindings = [], bool $exception = true): Saber\Response
    {
        return $this->http->write($sql, $bindings, $exception);
    }

    /**
     * @return Settings
     */
    public function settings(): Settings
    {
        return $this->http->settings();
    }

    /**
     * set db name
     * @return static
     */
    public function database(string $db)
    {
        $this->settings()->database($db);
        return $this;
    }
}