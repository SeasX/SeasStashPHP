<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/12/28
 * Time: 10:24
 */

namespace SeasStash\Timer;

/**
 * Class AbstractTimer
 * @package SeasStash\Timer
 */
abstract class AbstractTimer
{
    /**
     * 日志统计前缀
     */
    const TIMER_PREFIX = "timer";

    const TYPE_AFTER = 'after';
    const TYPE_TICKET = 'tick';

    /**
     * @var array 所有定时器
     */
    protected static $timers = [];

    /**
     * @return array
     */
    public function getTimers(): array
    {
        return self::$timers;
    }

    /**
     * @param string $name
     * @param null $default
     * @return array
     */
    public function getTimer(string $name, $default = null): array
    {
        return isset(self::$timers[$name]) ? self::$timers[$name] : $default;
    }

    /**
     * @param array $params
     */
    protected static function run(array $params)
    {
        if (count($params) < 2) {
            return;
        }
        $name = array_shift($params);
        $type = array_shift($params);
        $callback = array_shift($params);

        $callbackParams = array_values($params);

        if (is_array($callback)) {
            list($class, $method) = $callback;
            $class->$method(...$callbackParams);
        } elseif ($callback instanceof \Closure) {
            call_user_func($callback, $callbackParams);
        } else {
            $callback(...$callbackParams);
        }
    }

    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    abstract static public function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int;

    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    abstract static public function addTickTimer(string $name, float $time, callable $callback, array $params = []): int;

    /**
     * @param string $name
     * @return bool
     */
    abstract static public function clearTimerByName(string $name): bool;

    /**
     * @return bool
     */
    abstract static public function clearTimers(): bool;
}