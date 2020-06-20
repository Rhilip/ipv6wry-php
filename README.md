# ipv6wry-php

The Parser Library of ipv6wry.db in PHP

## Built-in Database Update At

| Library Version | IP Database Version |
| :--: | :--: | 
| v0.1.0-v0.1.3 | 20190812 |
| v0.1.4 | 20200506 |

## Install and Usage

- Install via `Composer`

```bash
composer require rhilip/ipv6wry
```

- Usage

```php
require 'vendor/autoload.php';

use Rhilip\Ipv6Wry\IpLocation;

// Use Custom IP Database instead of Built-in
IpLocation::setDbPath($db_path);

// Search IPv6 Address and get Location information
IpLocation::searchIp($ipv6_address);
```

- Return (In JSON format)

If Success
```json
{
  "ip":"2001:da8:200:900e:0:5efe:182.117.109.0",
  "area":"中国北京市 清华大学"
}
```

If Failed

```json
{
  "error":"Input ip address is not in IPv6 format."
}
```

## Update Database

This Library doesn't provide Database Update methods, But you can download Database from :

 - Official Website : <http://ip.zxinc.org/index.htm>
 - Auto-Sync Repo : <https://github.com/Rhilip/ipv6wry.db>
 
## Changelog

```
v0.1.3 Fix binarySearch() may too early to return
v0.1.2 Fix unpack error for 3 bytes string to int
v0.1.1 Fix IpLocation::setDbPath can't change self::$db_path when call twice before instance.
v0.1.0 Init Commit.
```

## License

 - This Repo: [GPL-3.0-only](https://github.com/Rhilip/ipv6wry.db/blob/master/LICENSE)
 - IP Database for `ipv6wry.db`:
 
```
本协议是用户（您）和ZX公司（zxinc.org）之间关于使用ZX IP地址数据库（本数据库）达成的协议。您安装或者使用本数据库的行为将视为对本协的接受及同意。除非您接受本协议，否则请勿下载、安装或使用本数据库，并请将本数据库从计算机中移除。

1. 本数据库是免费许可软件，不进行出售。你可以免费的复制，分发和传播本数据库，但您必须保证每一份复制、分发和传播都必须是未更改过的，完整和真实的。
2. 您作为个人使用本数据库。您只能对本数据库进行非商业性的应用。
3. 任何免费软件以及非商业性网站均可无偿使用本数据库，但在其说明上均应注明本数据库的名称和来源为“ZX IP地址数据库”。
4. 本数据库为免费共享软件。我们对本数据库产品不提供任何保证，不对任何用户因本数据库所遭遇到的任何理论上的或实际上的损失承担责任，不对用户使用本数据库造成的任何后果承担责任。
5. 本数据库所收集的信息，均是从网上收集而来。数据库只包含IP与其对应的地址，但是这些数据不会涉及您的个人信息，因此也不会侵害您的隐私。
6. 欢迎任何人为我们提供正确详尽的IP地址。可登录网站（http://ip.zxinc.org）或论坛（http://bbs.zxinc.org）提交正确的IP与地址，以便我们修正并提高本数据库IP地址数据的准确性。

		ZX公司（zxinc.org）版权所有，保留一切解释权利 !
```