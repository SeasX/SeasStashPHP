<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/11/26
 * Time: 16:59
 */

namespace SeasStash\Message;

defined('DATEFORMAT') or define('DATEFORMAT', ini_get('seaslog.default_datetime_format'));

/**
 * Class Rfc5424
 * @package SeasStash\Message
 */
class Rfc5424
{
    /** @var int */
    public $priority;
    /** @var int */
    public $severity;
    /** @var int */
    public $facility;
    /** @var int */
    public $version;
    /** @var string */
    public $dateTime;
    public $machineName;
    /** @var string */
    public $hostName;
    /** @var string */
    public $appName;
    /** @var static */
    public $processID;
    /** @var string */
    public $msg;
    /** @var TemplateMessage */
    public $templateMsg;

    /**
     * RFC5424Message constructor.
     * @param string $rfcMsg
     */
    public function __construct(string $rfcMsg)
    {
        $input = explode(" ", $rfcMsg);
        $input = array_slice($input, 0, 6);
        preg_match_all('/<(.*?)>(.*?)$/', $input[0], $matches);
        if (isset($matches[1][0])) {
            $this->priority = (int)$matches[1][0];
        }
        if (isset($matches[2][0])) {
            $this->version = (int)$matches[2][0];
        }
        $this->severity = $this->priority % 8;
        $this->facility = $this->priority >> 3;
        $this->dateTime = date(DATEFORMAT, strtotime($input[1]));
        $this->machineName = $input[2];
        $this->hostName = $input[3];
        $this->processID = $input[4];
        $this->appName = $input[5];
        $this->msg = trim(substr($rfcMsg, $this->str_n_pos($rfcMsg, ' ', 6)));
        $this->templateMsg = (new TemplateMessage($this->msg))->toArray();
    }

    /**
     * @param string $str
     * @param string $find
     * @param int $n
     * @return int
     */
    private function str_n_pos(string $str, string $find, int $n): int
    {
        $pos_val = 0;
        for ($i = 1; $i <= $n; $i++) {
            $pos = strpos($str, $find);
            $str = substr($str, $pos + 1);
            $pos_val = $pos + $pos_val + 1;
        }
        return $pos_val - 1;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return \get_object_vars($this);
    }
}