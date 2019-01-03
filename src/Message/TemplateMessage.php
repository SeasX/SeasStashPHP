<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/11/26
 * Time: 20:11
 */

namespace SeasStash\Message;

defined('TEMPLATE') or define('TEMPLATE', ini_get('seaslog.default_template'));

/**
 * Class TemplateMessage
 * @package SeasStash\Message
 */
class TemplateMessage
{
    /** @var array */
    private $attributes = [];
    /** @var string */
    private $split = '|';
    /** @var array */
    private static $template = [
        '%L' => ['level', 'string'],
        '%M' => ['message', 'string'],
        '%T' => ['datetime', 'string'],
        '%t' => ['timestamp', 'int'],
        '%Q' => ['requestid', 'string'],
        '%H' => ['hostname'], 'string',
        '%P' => ['processid', 'string'],
        '%D' => ['domain', 'string'],
        '%R' => ['request_uri', 'string'],
        '%m' => ['request_method', 'string'],
        '%I' => ['clientip', 'string'],
        '%F' => ['filename', 'string'],
        '%U' => ['memoryusage', 'int'],
        '%u' => ['peak_memoryusage', 'int'],
        '%C' => ['class', 'string']
    ];

    /**
     * TemplateMessage constructor.
     * @param string $msg
     */
    public function __construct(string $msg)
    {
        $msgArray = explode($this->split, $msg);
        $tempArray = explode($this->split, TEMPLATE);
        if (count($msgArray) === count($tempArray)) {
            foreach ($msgArray as $index => $value) {
                $tempArray[$index] = trim($tempArray[$index]);
                list($name, $type) = self::$template[$tempArray[$index]];
                switch ($type) {
                    case 'int':
                        $value = (int)trim($value);
                        break;
                    case 'string':
                        $value = (string)trim($value);
                        break;
                    default:
                        $value = (string)trim($value);
                }
                $this->attributes[$name] = $value;
            }
        }
    }

    /**
     * @param $name
     */
    public function __get($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}