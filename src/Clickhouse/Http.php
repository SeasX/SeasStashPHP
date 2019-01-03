<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/12/11
 * Time: 9:22
 */

namespace SeasStash\Clickhouse;

use SeasStash\Clickhouse\Query\Degeneration;
use SeasStash\Clickhouse\Query\Query;
use SeasStash\Clickhouse\Query\WhereInFile;
use SeasStash\Clickhouse\Query\WriteToFile;
use Swlib\Saber;

/**
 * Class Http
 * @package Clickhouse
 */
class Http
{
    /** @var Saber */
    private $saber;

    /**
     * @var Settings
     */
    private $settings = null;

    private $bufferSize = 1024 * 1024 * 128;

    /**
     * @var array
     */
    private $query_degenerations = [];

    /**
     * Http constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->saber = Saber::create($options);

        $this->settings = new Settings();
        isset($options['timeout']) && $this->settings->max_execution_time($options['timeout']);
    }

    /**
     * @return Saber
     */
    public function getClient(): Saber
    {
        return $this->saber;
    }

    /**
     * @param $sql
     * @param array $bindings
     * @param null $whereInFile
     * @param null $writeToFile
     * @return Saber\Response
     */
    public function select(string $sql, array $bindings = [], WhereInFile $whereInFile = null, WriteToFile $writeToFile = null): Saber\Response
    {
        $request = $this->prepareSelect($sql, $bindings, $whereInFile, $writeToFile);
        return $request->recv();
    }

    /**
     * @param $sql
     * @param $bindings
     * @param $whereInFile
     * @param null $writeToFile
     * @return Saber\Request
     */
    private function prepareSelect(string $sql, array $bindings, WhereInFile $whereInFile = null, WriteToFile $writeToFile = null): Saber\Request
    {
        if ($sql instanceof Query) {
            return $this->getRequestWrite($sql);
        }
        $query = $this->prepareQuery($sql, $bindings);
        $query->setFormat('JSON');
        return $this->getRequestRead($query, $whereInFile, $writeToFile);
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @return Query
     */
    private function prepareQuery(string $sql, array $bindings): Query
    {

        // add Degeneration query
        foreach ($this->query_degenerations as $degeneration) {
            $degeneration->bindParams($bindings);
        }

        return new Query($sql, $this->query_degenerations);
    }

    /**
     * @param $sql
     * @param array $bindings
     * @return Saber\Request
     */
    private function prepareWrite(string $sql, array $bindings = [])
    {
        if ($sql instanceof Query) {
            return $this->getRequestWrite($sql);
        }

        $query = $this->prepareQuery($sql, $bindings);
        return $this->getRequestWrite($query);
    }

    /**
     * @return bool
     */
    public function cleanQueryDegeneration(): bool
    {
        $this->query_degenerations = [];
        return true;
    }

    /**
     * @param Degeneration $degeneration
     * @return bool
     */
    public function addQueryDegeneration(Degeneration $degeneration): bool
    {
        $this->query_degenerations[] = $degeneration;
        return true;
    }

    /**
     * @param Query $query
     * @return Saber\Request
     */
    public function getRequestWrite(Query $query): Saber\Request
    {
        $urlParams = ['readonly' => 0];
        return $this->makeRequest($query, $urlParams);
    }

    /**
     * @param Query $query
     * @param array $urlParams
     * @param bool $query_as_string
     * @return Saber\Request
     */
    private function makeRequest(Query $query, array $urlParams = [], bool $query_as_string = false): Saber\Request
    {
        $sql = $query->toSql();

        if ($query_as_string) {
            $urlParams['query'] = $sql;
        }
        $urlParams = $this->getQueryParams($urlParams);
        $request = $this->saber->post('', $sql, [
            'uri_query' => $urlParams,
            'wait' => true,
            'before' => function (\Swlib\Saber\Request $request) {
                $request->proxy = ['socket_buffer_size' => $this->bufferSize];
            },
        ]);
        return $request;
    }

    /**
     * @param array $params
     * @return array
     */
    private function getQueryParams(array $params = []): array
    {
        $settings = $this->settings->getSettings();

        if (is_array($params) && sizeof($params)) {
            $settings = array_merge($settings, $params);
        }


        if ($this->settings->isReadOnlyUser()) {
            unset($settings['extremes']);
            unset($settings['readonly']);
            unset($settings['enable_http_compression']);
            unset($settings['max_execution_time']);

        }
        return $settings;
    }

    /**
     * @param Query $query
     * @param WhereInFile|null $whereInFile
     * @param WriteToFile|null $writeToFile
     * @return Saber\Request
     */
    public function getRequestRead(Query $query, WhereInFile $whereInFile = null, WriteToFile $writeToFile = null): Saber\Request
    {
        $urlParams = ['readonly' => 1];
        $query_as_string = false;
        // ---------------------------------------------------------------------------------
        if ($whereInFile instanceof WhereInFile && $whereInFile->size()) {
            // $request = $this->prepareSelectWhereIn($request, $whereInFile);
            $structure = $whereInFile->fetchUrlParams();
            // $structure = [];
            $urlParams = array_merge($urlParams, $structure);
            $query_as_string = true;
        }
        // ---------------------------------------------------------------------------------
        // if result to file
        if ($writeToFile instanceof WriteToFile && $writeToFile->fetchFormat()) {
            $query->setFormat($writeToFile->fetchFormat());
            unset($urlParams['extremes']);
        }
        // ---------------------------------------------------------------------------------
        // attach files
        if ($whereInFile instanceof WhereInFile && $whereInFile->size()) {
            $urlParams['files'] = $whereInFile->fetchFiles();
        }
        // ---------------------------------------------------------------------------------
        // result to file
        if ($writeToFile instanceof WriteToFile && $writeToFile->fetchFormat()) {
            $urlParams['download_dir'] = $writeToFile->fetchFile();
        }
        // ---------------------------------------------------------------------------------
        // makeRequest read
        $request = $this->makeRequest($query, $urlParams, $query_as_string);

        // ---------------------------------------------------------------------------------
        return $request;
    }

    /**
     * @param $sql
     * @param array $bindings
     * @param bool $exception
     * @return Saber\Response
     */
    public function write($sql, array $bindings = [], $exception = true): Saber\Response
    {
        return $this->prepareWrite($sql, $bindings)->recv();
    }

    /**
     * @return Settings
     */
    public function settings(): Settings
    {
        return $this->settings;
    }
}