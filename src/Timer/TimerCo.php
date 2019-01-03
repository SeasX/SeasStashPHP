<?php

namespace SeasStash\Timer;

/**
 * Class TimerCo
 * @package SeasStash\Timer
 */
class TimerCo extends AbstractTimer
{
    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    public static function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        array_unshift($params, $name ?? uniqid(), self::TYPE_AFTER, $callback);
        self::$timers[$name] = ['name' => $name, 'type' => self::TYPE_AFTER];
        $tid = go(function () use ($time, $params) {
            \Co::sleep($time / 1000);
            self::timerCallback($params);
        });
        self::$timers[$name]['tid'] = $tid;
        return $tid;
    }

    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    public static function addTickTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        array_unshift($params, $name ?? uniqid(), self::TYPE_AFTER, $callback);
        self::$timers[$name] = ['name' => $name, 'type' => self::TYPE_AFTER];
        $tid = go(function () use ($name, $time, $params) {
            while (isset(self::$timers[$name])) {
                self::timerCallback($params);
                \Co::sleep($time / 1000);
            }
        });
        self::$timers[$name]['tid'] = $tid;
        return $tid;
    }

    /**
     * 移除一个定时器
     *
     * @param string $name 定时器名称
     *
     * @return bool
     */
    public static function clearTimerByName(string $name): bool
    {
        if (!isset(self::$timers[$name])) {
            return true;
        }
        unset(self::$timers[$name]);
        return true;
    }

    /**
     * @return bool
     */
    public static function clearTimers(): bool
    {
        self::$timers = [];
        return true;
    }

    /**
     * 定时器回调函数
     *
     * @param array $params 参数传递
     */
    public static function timerCallback(array $params): void
    {
        self::run($params);
    }
}
