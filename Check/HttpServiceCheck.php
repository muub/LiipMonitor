<?php

namespace Liip\Monitor\Check;

use Liip\Monitor\Check\Check;
use Liip\Monitor\Exception\CheckFailedException;
use Liip\Monitor\Result\CheckResult;

class HttpServiceCheck extends Check
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var int
     */
    protected $content;

    /**
     * @param string $host
     * @param int $port
     */
    public function __construct($host, $port = 80, $path = '/', $statusCode = 200, $content = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->statusCode = $statusCode;
        $this->content = $content;
    }

    /**
     * @see Liip\MonitorBundle\Check\CheckInterface::check()
     */
    public function check()
    {
        $fp = @fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$fp) {
            $result = $this->buildResult(sprintf('No http service running at host %s on port %s', $this->host, $this->port), CheckResult::CRITICAL);
        } else {
            $header = "GET {$this->path} HTTP/1.1\r\n";
            $header .= "Host: {$this->host}\r\n";
            $header .= "Connection: close\r\n\r\n";
            fputs($fp, $header);
            $str = '';
            while (!feof($fp)) {
                $str .= fgets($fp, 1024);
            }
            fclose($fp);

            if ($this->statusCode && strpos($str, "HTTP/1.1 {$this->statusCode}") !== 0) {
                $result = $this->buildResult("Status code {$this->statusCode} does not match in response from {$this->host}:{$this->port}{$this->path}", CheckResult::CRITICAL);
            } elseif ($this->content && !strpos($str, $this->content)) {
                $result = $this->buildResult("Content {$this->content} not found in response from {$this->host}:{$this->port}{$this->path}", CheckResult::CRITICAL);
            } else {
                $result = $this->buildResult('OK', CheckResult::OK);
            }
        }

        return $result;
    }

    /**
     * @see Liip\MonitorBundle\Check\Check::getName()
     */
    public function getName()
    {
        return 'Http Service ('.$this->host.')';
    }
}
