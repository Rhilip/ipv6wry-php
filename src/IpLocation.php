<?php
/**
 * Created by PhpStorm.
 * User: Rhilip
 * Date: 9/18/2019
 * Time: 2019
 */

namespace Rhilip\Ipv6Wry;

use RuntimeException;
use Throwable;

class IpLocation
{
    /**
     * 使用单例模式
     * @var self $instance
     */
    protected static $instance;

    /**
     * ipv6wry.db 文件位置
     * @var string
     */
    protected static $db_path;

    /**
     * ipv6wry.db 文件指针
     * @var resource $handle
     */
    protected $handle;

    /**
     * 偏移地址长度(2~8)
     * @var int $osLen 解析出来是 3
     */
    protected $osLen;

    /**
     * IP地址长度(4或8或12或16, 现在只支持4(ipv4)和8(ipv6))
     * @var int $ipLen 解析出来是 8
     */
    protected $ipLen;

    /**
     * 索引区第一条记录的偏移
     * @var int $dbAddr
     */
    protected $dbAddr;

    /**
     * 记录数
     * @var int $size
     */
    protected $size;

    /**
     * @var int
     */
    private $dLen;

    /**
     * IpLocation constructor.
     */
    const ERROR_DATABASE_READABLE = 'Failed open ip database file!';
    const ERROR_DATABASE_TYPE = 'The type of ip database file is not "IPDB".';
    const ERROR_DATABASE_RESET_PATH = 'The Path of ip database file can\'t change after instance.';
    const ERROR_NOT_IPV6_FORMAT = 'Input ip address is not in IPv6 format.';
    const ERROR_UNKNOWN_ADDRESS = 'Unknown Address.';
    const ERROR_SEARCH_INDEX_OUT_RANGE = 'Index out of range';

    private final function __construct()
    {
        // 打开 ipv6wry.db 文件句柄
        $filename = self::setDbPath();
        $this->handle = fopen($filename, 'rb');

        // 检查是不是IPDB类型数据库
        if ($this->read(0, 4) !== 'IPDB') {
            throw new RuntimeException(self::ERROR_DATABASE_TYPE);
        }

        // 获取数据库基本信息
        $this->osLen = $this->readInt(6, 1);
        $this->ipLen = $this->readInt(7, 1);
        $this->dLen = $this->osLen + $this->ipLen;
        $this->dbAddr = $this->readInt(0x10, 8);  // size = 8
        $this->size = $this->readInt(8, 8);  // size = 8
    }

    /**
     * 设定 ipv6wry.db 数据库位置
     * 如需更换，需在单例实例之前调用
     *
     * @param string $db_path
     * @return string self::$db_path
     */
    public static function setDbPath(string $db_path = null)
    {
        // 检查是否已经实例化
        if (self::$instance !== null)
            throw new RuntimeException(self::ERROR_DATABASE_RESET_PATH);

        // 使用内置的数据库，如果用户没有指定外部数据库的话
        self::$db_path = self::$db_path ?? $db_path ?? __DIR__ . '/ipv6wry.db';

        // 检查文件是否存在以及文件权限
        if (!is_readable(self::$db_path)) {
            throw new RuntimeException(self::ERROR_DATABASE_READABLE);
        }

        return self::$db_path;
    }

    /**
     * 获取单例
     *
     * @return self
     */
    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Low-level, 按 offset以及size 读取 Bytes
     *
     * @param int $offset Position from where to start reading
     * @param int $size Read this many bytes
     * @return string
     */
    private function read(int $offset = 0, int $size = 1): string
    {
        fseek($this->handle, $offset, SEEK_SET);
        return fread($this->handle, $size);
    }

    /**
     * Low-level, 获取 Binary 形式的数字
     *
     * @param int $offset Position from where to start reading
     * @param int $size Read this many bytes
     * @return int
     */
    private function readInt(int $offset = 0, int $size = 8): int
    {
        $s = $this->read($offset, $size);
        $format = [8 => 'P', 4 => 'V', 3 => 'v', 2 => 's', 1 => 'C'][$size];

        return unpack($format, $s)[1];
    }

    /**
     * Higher-Level, 从 $start 开始读取解析出字符串 （utf-8）
     *
     * @param int $start Position from where to start reading
     * @return string
     */
    private function readRawText(int $start): string
    {
        $bs = '';

        # 使用循环读取，0为终止
        while (0 != $p = $this->readInt($start, 1)) {
            $bs .= chr($p);
            $start += 1;
        }

        return $bs;
    }

    /**
     * 将IPv6的前4个字段转换为长整数
     *
     * @param string $ip The IPv6 Address
     * @return int the first 4 pieces of IPv6 Address
     */
    private function parseIpv6(string $ip): int
    {
        // 检查是不是ipv6
        $ipv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        if ($ipv6 === false) return -1;

        // 补全 ::
        $count = substr_count($ipv6, ':');
        $ipv6 = preg_replace('/::/', str_repeat(':', 8 - $count + 1), $ipv6, 1);

        // 我们只要前4个，并将其将其转换为整数
        $v6_prefix_long = 0;
        $subs = array_slice(explode(':', $ipv6), 0, 4);
        foreach ($subs as $sub) {
            if (strlen($sub) > 4) return -1;
            $v6_prefix_long = bcadd(bcmul($v6_prefix_long, 0x10000), intval($sub, 16));
        }
        return (int)$v6_prefix_long;
    }

    private function checkIndex(int $index)
    {
        if ($index < 0 || $index >= $this->size)
            throw new RuntimeException(self::ERROR_SEARCH_INDEX_OUT_RANGE);
    }

    private function getData(int $index): int
    {
        $this->checkIndex($index);
        $addr = $this->dbAddr + $index * $this->dLen;
        return $this->readInt($addr, $this->ipLen);
    }

    /**
     * @param int $start
     * @param bool $isTwoPart
     * @return string
     */
    private function readLoc(int $start, bool $isTwoPart = False): string
    {
        $jType = $this->readInt($start, 1);
        if ($jType == 1 || $jType == 2) {
            $start += 1;
            $offAddr = $this->readInt($start, $this->osLen);
            if ($offAddr == 0)
                throw new RuntimeException(self::ERROR_UNKNOWN_ADDRESS);

            $loc = $this->readLoc($offAddr, $jType == 1);
            $nAddr = $start + $this->osLen;
        } else {
            $loc = $this->readRawText($start);
            $nAddr = $start + strlen($loc) + 1;
        }

        if ($isTwoPart && $jType != 1) {
            $part2 = $this->readLoc($nAddr);
            if ($loc && $part2) $loc .= ' ' . $part2;
        }
        return $loc;
    }

    /**
     * 使用二分法查找网络字节编码的IP地址的索引记录
     *
     * @param $key
     * @param int $lo
     * @param int $hi
     * @return int
     */
    private function binarySearch(int $key, int $lo = 0, int $hi = null): int
    {
        $hi = $hi ?? $this->size - 1;

        while ($lo < $hi) {
            if ($hi - $lo <= 1) {
                if ($this->getData($lo) > $key) {
                    return -1;
                } elseif ($this->getData($hi) <= $key) {
                    return $hi;
                } else {
                    return $lo;
                }
            }
            $mid = (int)floor(($hi + $lo) / 2);
            $data = $this->getData($mid);
            if ($data > $key) {
                $hi = $mid - 1;
            } elseif ($data < $key) {
                $lo = $mid;
            } else {
                return $mid;
            }
        }
        return -1;
    }

    private function getLoc(int $index): string
    {
        $this->checkIndex($index);
        $addr = $this->dbAddr + $index * $this->dLen;
        $lAddr = $this->readInt($addr + $this->ipLen, $this->osLen);
        $loc = $this->readLoc($lAddr, true);
        return $loc;
    }

    /** @noinspection PhpUnused */
    public static function searchIp(string $ipv6): array
    {
        try {
            $instance = self::getInstance();
            $v6_int = $instance->parseIpv6($ipv6);

            if ($v6_int < 0)
                throw new RuntimeException(self::ERROR_NOT_IPV6_FORMAT);

            $index = $instance->binarySearch($v6_int);

            if ($index < 0)
                throw new RuntimeException(self::ERROR_UNKNOWN_ADDRESS);

            if ($index > $instance->size - 2) $index = $instance->size - 2;

            $area = $instance->getLoc($index);

            return [
                'ip' => $ipv6,
                'area' => $area
            ];
        } catch (Throwable $exception) {
            return [
                'error' => $exception->getMessage()
            ];
        }
    }
}
