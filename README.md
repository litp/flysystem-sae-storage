# flysystem-sae-storage

[SinaAppEngine](https://sae.sina.com.cn) Storage服务的Flysystem Adapter.

## 安装
通过composer安装
```bash
composer require litp/flysystem-sae-storage
```

## 使用
生成filesystem对象：
```php
use League\Flysystem\Filesystem;
use Litp\Flysystem\Storage;
use Litp\Flysystem\StorageAdapter;

$bucket = 'name of bucket here';

$client = new Storage();
$adapter = new StorageAdapter($client,$bucket);
$filesystem = new Filesystem($adapter);
```
使用filesystem对象，更多参见[Flysystem网站](http://flysystem.thephpleague.com/)：
```php
$filesystem->has('path/to/file');
$filesystem->put('path/to/file');
$filesystem->read('path/to/file');
```