  <?php
  
  namespace sinacloud\sae;
  
  if (defined('SAE_APPNAME')) {
      define('DEFAULT_STORAGE_ENDPOINT', 'api.i.sinas3.com:81');
      define('DEFAULT_USE_SSL', false);
  } else {
      define('DEFAULT_STORAGE_ENDPOINT', 'api.sinas3.com');
      define('DEFAULT_USE_SSL', true);
  }
  
  /**
   * SAE Storage PHP客户端
   *
   * @copyright Copyright (c) 2015, SINA, All rights reserved.
   *
   * ```php
   * <?php
   * use sinacloud\sae\Storage as Storage;
   *
   * **类初始化**
   *
   * // 方法一：在SAE运行环境中时可以不传认证信息，默认会从应用的环境变量中取
   * $s = new Storage();
   *
   * // 方法二：如果不在SAE运行环境或者要连非本应用的storage，需要传入所连应用的"应用名:应用AccessKey"和"cretKey"
   * $s = new Storage("$AppName:$AccessKey", $SecretKey);
   *
   * **Bucket操作**
   *
   * // 创建一个Bucket test
   * $s->putBucket("test");
   *
   * // 获取Bucket列表
   * $s->listBuckets();
   *
   * // 获取Bucket列表及Bucket中Object数量和Bucket的大小
   * $s->listBuckets(true);
   *
   * // 获取test这个Bucket中的Object对象列表，默认返回前1000个，如果需要返回大于1000个Object的列表，可以mit参数来指定。
   * $s->getBucket("test");
   *
   * // 获取test这个Bucket中所有以 *a/* 为前缀的Objects列表
   * $s->getBucket("test", 'a/');
   *
   * // 获取test这个Bucket中所有以 *a/* 为前缀的Objects列表，只显示 *a/N* 这个Object之后的列表（不包含 * 这个Object）。
   * $s->getBucket("test", 'a/', 'a/N');
   *
   * // Storage也可以当成一个伪文件系统来使用，比如获取 *a/下的Object（不显示其下的子目录的具体Object名称，只显示目录名）
  * $s->getBucket("test", 'a/', null, 10000, '/');
  *
  * // 删除一个空的Bucket test
  * $s->deleteBucket("test");
  *
  * **Object上传操作**
  *
  * // 把$_FILES全局变量中的缓存文件上传到test这个Bucket，设置此Object名为1.txt
  * $s->putObjectFile($_FILES['uploaded']['tmp_name'], "test", "1.txt");
  *
  * // 把$_FILES全局变量中的缓存文件上传到test这个Bucket，设置此Object名为sae/1.txt
  * $s->putObjectFile($_FILES['uploaded']['tmp_name'], "test", "sae/1.txt");
  *
  * // 上传一个字符串到test这个Bucket中，设置此Object名为string.txt，并且设置其Content-type
  * $s->putObject("This is string.", "test", "string.txt", Storage::ACL_PUBLIC_READ, array(),y('Content-Type' => 'text/plain'));
  *
  * /一个文件句柄（必须是buffer或者一个文件，文件会被自动fclose掉）到test这个Bucket中，设置此Object名为file.txt
  * $s->putObject(Storage::inputResource(fopen($_FILES['uploaded']['tmp_name'], 'rb'),size($_FILES['uploaded']['tmp_name']), "test", "file.txt", Storage::ACL_PUBLIC_READ);
  *
  * **Object下载操作**
  *
  * // 从test这个Bucket读取Object 1.txt，输出为此次请求的详细信息，包括状态码和1.txt的内容等
  * var_dump($s->getObject("test", "1.txt"));
  *
  * // 从test这个Bucket读取Object 1.txt，把1.txt的内容保存在SAE_TMP_PATH变量指定的TmpFS中，savefile.保存的文件名;SAE_TMP_PATH路径具有写权限，用户可以往这个目录下写文件，但文件的生存周期等同于PHP请求，也就是P请求完成执行时，所有写入SAE_TMP_PATH的文件都会被销毁
   * $s->getObject("test", "1.txt", SAE_TMP_PATH."savefile.txt");
   *
   * // 从test这个Bucket读取Object 1.txt，把1.txt的内容保存在打开的文件句柄中 
   * $s->getObject("test", "1.txt", fopen(SAE_TMP_PATH."savefile.txt", 'wb'));
   *
   * **Object删除操作**
   *
   * // 从test这个Bucket删除Object 1.txt 
   * $s->deleteObject("test", "1.txt");
   *
   * **Object复制操作**
   *
   * // 把test这个Bucket的Object 1.txt内容复制到newtest这个Bucket的Object 1.txt
   * $s->copyObject("test", "1.txt", "newtest", "1.txt");
   *
   * // 把test这个Bucket的Object 1.txt内容复制到newtest这个Bucket的Object 1.并设置Object的浏览器缓存过期时间为10s和Content-Type为text/plain
   * $s->copyObject("test", "1.txt", "newtest", "1.txt", array('expires' => '10s'), array(tent-Type' => 'text/plain'));
   *
   * **生成一个外网能够访问的url**
   *
   * // 为私有Bucket test中的Object 1.txt生成一个能够在外网用GET方法临时访问的URL，次URL过期时间为600s
   * $s->getTempUrl("test", "1.txt", "GET", 600);
   *
   * // 为test这个Bucket中的Object 1.txt生成一个能用CDN访问的URL
 1 * $s->getCdnUrl("test", "1.txt");
 1 *
 1 * **调试模式**
 1 *
 1 * //试模式，出问题的时候方便定位问题，设置为true后遇到错误的时候会抛出异常而不是写一条warning信息到日志。
 1 * $s->setExceptions(true);
 1 * ?>
 1 * ```
 1 */
 1
 1class Storage
 1{
 1    // ACL flags
 1    const ACL_PRIVATE = '';
 1    const ACL_PUBLIC_READ = '.r:*';
 1
 1    private static $__accessKey = null;
 1    private static $__secretKey = null;
 1    private static $__account = null;
 1
 1    /**
 1     * 默认使用的分隔符，getBucket()等用到
 1     *
 1     * @var string
 1     * @access public
 1     * @static
 1     */
 1    public static $defDelimiter = null;
 1
 1    public static $endpoint = DEFAULT_STORAGE_ENDPOINT;
 1
 1    public static $proxy = null;
 1
 1    /**
 1     * 使用SSL连接？
 1     *
 1     * @var bool
 1     * @access public
 1     * @static
 1     */
 1    public static $useSSL = DEFAULT_USE_SSL;
 1
 1    /**
 1     * 是否验证SSL证书
 1     *
 1     * @var bool
 1     * @access public
 1     * @static
 1     */
 1    public static $useSSLValidation = false;
 1
 1    /**
 1     * 使用的SSL版本
 1     *
 1     * @var const
 1     * @access public
 1     * @static
 1     */
 1    public static $useSSLVersion = 1;
 1
 1    /**
 1     * 出现错误的时候是否使用PHP Exception（默认使用trigger_error纪录错误）
 1     *
 1     * @var bool
 1     * @access public
 1     * @static
 1     */
 1    public static $useExceptions = false;
 1
 1    /**
 1     * 构造函数
 1     *
 1     * @param string $accessKey 此处需要使用"应用名:应用Accesskey"
 1     * @param string $secretKey 应用Secretkey
 1     * @param boolean $useSSL 是否使用SSL
 1     * @param string $endpoint SAE Storage的endpoint
 1     * @return void
 1     */
 1    public function __construct($accessKey = null, $secretKey = null,
 1            $useSSL = DEFAULT_USE_SSL, $endpoint = DEFAULT_STORAGE_ENDPOINT)
 1    {
 1        if ($accessKey !== null && $secretKey !== null) {
 1            self::setAuth($accessKey, $secretKey);
 1        } else if (defined('SAE_APPNAME')) {
 1            // We are in SAE Runtime
 1            self::setAuth(SAE_APPNAME.':'.SAE_ACCESSKEY, SAE_SECRETKEY);
 1        }
 1        self::$useSSL = $useSSL;
 1        self::$endpoint = $endpoint;
 1    }
 1
 1
 1    /**
 1     * 设置SAE的Storage的endpoint
 1     *
 1     * @param string $host SAE Storage的hostname
 1     * @return void
 1     */
 1    public function setEndpoint($host)
 1    {
 2        self::$endpoint = $host;
 2    }
 2
 2
 2    /**
 2     * 设置访问的Accesskey和Secretkey
 2     *
 2     * @param string $accessKey 此处需要使用"应用名:应用Accesskey"
 2     * @param string $secretKey 应用Secretkey
 2     * @return void
 2     */
 2    public static function setAuth($accessKey, $secretKey)
 2    {
 2        $e = explode(':', $accessKey);
 2        self::$__account = $e[0];
 2        self::$__accessKey = $e[1];
 2        self::$__secretKey = $secretKey;
 2    }
 2
 2
 2    public static function hasAuth() {
 2        return (self::$__accessKey !== null && self::$__secretKey !== null);
 2    }
 2
 2
 2    /**
 2     * 开启或者关闭SSL
 2     *
 2     * @param boolean $enabled 是否启用SSL
 2     * @param boolean $validate 是否验证SSL证书
 2     * @return void
 2     */
 2    public static function setSSL($enabled, $validate = true)
 2    {
 2        self::$useSSL = $enabled;
 2        self::$useSSLValidation = $validate;
 2    }
 2
 2
 2    /**
 2     * 设置代理信息
 2     *
 2     * @param string $host 代理的hostname和端口(localhost:1234)
 2     * @param string $user 代理的username
 2     * @param string $pass 代理的password
 2     * @param constant $type CURL代理类型
 2     * @return void
 2     */
 2    public static function setProxy($host, $user = null, $pass = null, $type = CROXY_SOCKS5)
 2    {
 2        self::$proxy = array('host' => $host, 'type' => $type, 'user' => $user, 'pass' => $);
 2    }
 2
 2
 2    /**
 2     * 设置是否使用PHP Exception，默认使用trigger_error
 2     *
 2     * @param boolean $enabled Enable exceptions
 2     * @return void
 2     */
 2    public static function setExceptions($enabled = true)
 2    {
 2        self::$useExceptions = $enabled;
 2    }
 2
 2
 2    private static function __triggerError($message, $file, $line, $code = 0)
 2    {
 2        if (self::$useExceptions)
 2            throw new StorageException($message, $file, $line, $code);
 2        else
 2            trigger_error($message, E_USER_WARNING);
 2    }
 2
 2
 2    /**
 2     * 获取bucket列表
 2     *
 2     * @param boolean $detailed 设置为true时返回bucket的详细信息
 2     * @return array | false
 2     */
 2    public static function listBuckets($detailed = false)
 2    {
 2        $rest = new StorageRequest('GET', self::$__account, '', '', self::$endpoint);
 2        $rest->setParameter('format', 'json');
 2        $rest = $rest->getResponse();
 2        if ($rest->error === false && $rest->code !== 200)
 2            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP statu        if ($rest->error !== false)        {            self::__triggerError(sprintf("Storage::listBuckets(): [%s] %s", $rest->erro'],                $rest->error['message']), __FILE__, __LINE__);            return false;        }        $buckets = json_decode($rest->body, True);        if ($buckets === False) {            self::__triggerError(sprintf("Storage::listBuckets(): invalid body: %s", $rest,                __FILE__, __LINE__);            return false;        }        if ($detailed) {            return $buckets;        }        $results = array();        foreach ($buckets as $b) $results[] = (string)$b['name'];        return $results;    }    /**     * 获取bucket中的object列表     *     * @param string $bucket Bucket名称     * @param string $prefix Object名称的前缀     * @param string $marker Marker (返回marker之后的object列表，不包含marker）     * @param string $limit 最大返回的Object数目     * @param string $delimiter 分隔符     * @return array | false     */    public static function getBucket($bucket, $prefix = null, $marker = null, $limit = 1000miter = null)    {        $result = array();        do {            $rest = new StorageRequest('GET', self::$__account, $bucket, '', self::$endpoi            $rest->setParameter('format', 'json')            if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix)            if ($marker !== null && $marker !== '') $rest->setParameter('marker', $marker)            if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimitermiter)            else if (!empty(self::$defDelimiter)) $rest->setParameter('delimiter:$defDelimiter)            if ($limit > 1000)                 $max_keys = 1000            } else                 $max_keys = $limit                        $rest->setParameter("limit", $max_keys)            $limit -= 1000            $response = $rest->getResponse()            if ($response->error === false && $response->code !== 200                $response->error = array('code' => $response->code, 'message' => 'Unexpectstatus')            if ($response->error !== false                            self::__triggerError(sprintf("Storage::getBucket(): [%s] %s", $respons['code']                    $response->error['message']), __FILE__, __LINE__)                return false                        $objects = json_decode($response->body, True)            if ($objects === False)                 self::__triggerError(sprintf("Storage::getBucket(): invalid body: %sonse->body)                    __FILE__, __LINE__)                return false                        if ($objects)                 $result = array_merge($result, $objects)                $marker = end($objects)                $marker = $marker['name']                    } while ($objects && count($objects) == $max_keys && $limit > 0)        return $result        /*     * 创建一个Bucke          * @param string $bucket Bucket名     * @param constant $acl Bucket的AC     * @param array $metaHeaders x-sws-container-meta-* header数     * @return boolea     *    public static function putBucket($bucket, $acl = self::ACL_PRIVATE, $metaHeaders=array()            $rest = new StorageRequest('PUT', self::$__account, $bucket, '', self::$endpoint)        if ($acl)             $rest->setSwsHeader('x-sws-container-read', $acl)                foreach ($metaHeaders as $k => $v)             $rest->setSwsHeader('x-sws-container-meta-'.$k, $v)                $rest = $rest->getResponse()        if ($rest->error === false && ($rest->code !== 201 && $rest->code != 202 && $res!== 204)            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP stat
 3        if ($rest->error !== false)
 390:         {
 391:             self::__triggerError(sprintf("Storage::putBucket({$bucket}, {$acl}): [%s] %s",
 392:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 393:             return false;
 394:         }
 395:         return true;
 396:     }
 397: 
 398:     /**
 399:      * 获取一个Bucket的属性
 400:      * @param string $bucket Bucket名称
 401:      * @param boolean $returnInfo 是否返回Bucket的信息
 402:      * @return mixed
 403:      */
 404:     public static function getBucketInfo($bucket, $returnInfo=True) {
 405:         $rest = new StorageRequest('HEAD', self::$__account, $bucket, '', self::$endpoint);
 406:         $rest = $rest->getResponse();
 407:         if ($rest->error === false && ($rest->code !== 204 && $rest->code !== 404))
 408:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 409:         if ($rest->error !== false)
 410:         {
 411:             self::__triggerError(sprintf("Storage::getBucketInfo({$bucket}): [%s] %s",
 412:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 413:             return false;
 414:         }
 415:         return $rest->code !== 404 ? $returnInfo ? $rest->headers : true : false;
 416:     }
 417: 
 418:     /**
 419:      * 修改一个Bucket的属性
 420:      *
 421:      * @param string $bucket Bucket名称
 422:      * @param constant $acl Bucket的ACL，null表示不变
 423:      * @param array $metaHeaders x-sws-container-meta-* header数组
 424:      * @return boolean
 425:      */
 426:     public static function postBucket($bucket, $acl = null, $metaHeaders=array())
 427:     {
 428:         $rest = new StorageRequest('POST', self::$__account, $bucket, '', self::$endpoint);
 429:         if ($acl) {
 430:             $rest->setSwsHeader('x-sws-container-read', $acl);
 431:         }
 432:         foreach ($metaHeaders as $k => $v) {
 433:             $rest->setSwsHeader('x-sws-container-meta-'.$k, $v);
 434:         }
 435: 
 436:         $rest = $rest->getResponse();
 437: 
 438:         if ($rest->error === false && ($rest->code !== 201 && $rest->code !== 204))
 439:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 440:         if ($rest->error !== false)
 441:         {
 442:             self::__triggerError(sprintf("Storage::postBucket({$bucket}, {$acl}): [%s] %s",
 443:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 444:             return false;
 445:         }
 446:         return true;
 447:     }
 448: 
 449:     /**
 450:      * 删除一个空的Bucket
 451:      *
 452:      * @param string $bucket Bucket名称
 453:      * @return boolean
 454:      */
 455:     public static function deleteBucket($bucket)
 456:     {
 457:         $rest = new StorageRequest('DELETE', self::$__account, $bucket, '', self::$endpoint);
 458:         $rest = $rest->getResponse();
 459:         if ($rest->error === false && $rest->code !== 204)
 460:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 461:         if ($rest->error !== false)
 462:         {
 463:             self::__triggerError(sprintf("Storage::deleteBucket({$bucket}): [%s] %s",
 464:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 465:             return false;
 466:         }
 467:         return true;
 468:     }
 469: 
 470: 
 471:     /**
 472:      * 为本地文件路径创建一个可以用于putObject()上传的array
 473:      *
 474:      * @param string $file 文件路径
 475:      * @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
 476:      * @return array | false
 477:      */
 478:     public static function inputFile($file, $md5sum = false)
 479:     {
 480:         if (!file_exists($file) || !is_file($file) || !is_readable($file))
 481:         {
 482:             self::__triggerError('Storage::inputFile(): Unable to open input file: '.$file, __FILE__, __LINE__);
 483:             return false;
 484:         }
 485:         clearstatcache(false, $file);
 486:         return array('file' => $file, 'size' => filesize($file), 'md5sum' => $md5sum !== false ?
 487:             (is_string($md5sum) ? $md5sum : base64_encode(md5_file($file, true))) : '');
 488:     }
 489: 
 490: 
 491:     /**
 492:      * 为打开的文件句柄创建一个可以用于putObject()上传的array
 493:      *
 494:      * @param string $resource Input resource to read from
 495:      * @param integer $bufferSize Input byte size
 496:      * @param string $md5sum MD5 hash to send (optional)
 497:      * @return array | false
 498:      */
 499:     public static function inputResource(&$resource, $bufferSize = false, $md5sum = '')
 500:     {
 501:         if (!is_resource($resource) || (int)$bufferSize < 0)
 502:         {
 503:             self::__triggerError('Storage::inputResource(): Invalid resource or buffer size', __FILE__, __LINE__);
 504:             return false;
 505:         }
 506: 
 507:         // Try to figure out the bytesize
 508:         if ($bufferSize === false)
 509:         {
 510:             if (fseek($resource, 0, SEEK_END) < 0 || ($bufferSize = ftell($resource)) === false)
 511:             {
 512:                 self::__triggerError('Storage::inputResource(): Unable to obtain resource size', __FILE__, __LINE__);
 513:                 return false;
 514:             }
 515:             fseek($resource, 0);
 516:         }
 517: 
 518:         $input = array('size' => $bufferSize, 'md5sum' => $md5sum);
 519:         $input['fp'] =& $resource;
 520:         return $input;
 521:     }
 522: 
 523: 
 524:     /**
 525:      * 上传一个object
 526:      *
 527:      * @param mixed $input Input data
 528:      * @param string $bucket Bucket name
 529:      * @param string $uri Object URI
 530:      * @param array $metaHeaders x-sws-object-meta-* header数组
 531:      * @param array $requestHeaders Array of request headers or content type as a string
 532:      * @return boolean
 533:      */
 534:     public static function putObject($input, $bucket, $uri, $metaHeaders = array(), $requestHeaders = array())
 535:     {
 536:         if ($input === false) return false;
 537:         $rest = new StorageRequest('PUT', self::$__account, $bucket, $uri, self::$endpoint);
 538: 
 539:         if (!is_array($input)) $input = array(
 540:             'data' => $input, 'size' => strlen($input),
 541:             'md5sum' => base64_encode(md5($input, true))
 542:         );
 543: 
 544:         // Data
 545:         if (isset($input['fp']))
 546:             $rest->fp =& $input['fp'];
 547:         elseif (isset($input['file']))
 548:             $rest->fp = @fopen($input['file'], 'rb');
 549:         elseif (isset($input['data']))
 550:             $rest->data = $input['data'];
 551: 
 552:         // Content-Length (required)
 553:         if (isset($input['size']) && $input['size'] >= 0)
 554:             $rest->size = $input['size'];
 555:         else {
 556:             if (isset($input['file'])) {
 557:                 clearstatcache(false, $input['file']);
 558:                 $rest->size = filesize($input['file']);
 559:             }
 560:             elseif (isset($input['data']))
 561:                 $rest->size = strlen($input['data']);
 562:         }
 563: 
 564:         // Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
 565:         if (is_array($requestHeaders))
 566:             foreach ($requestHeaders as $h => $v)
 567:                 strpos($h, 'x-') === 0 ? $rest->setSwsHeader($h, $v) : $rest->setHeader($h, $v);
 568:         elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
 569:             $input['type'] = $requestHeaders;
 570: 
 571:         // Content-Type
 572:         if (!isset($input['type']))
 573:         {
 574:             if (isset($requestHeaders['Content-Type']))
 575:                 $input['type'] =& $requestHeaders['Content-Type'];
 576:             elseif (isset($input['file']))
 577:                 $input['type'] = self::__getMIMEType($input['file']);
 578:             else
 579:                 $input['type'] = 'application/octet-stream';
 580:         }
 581: 
 582:         // We need to post with Content-Length and Content-Type, MD5 is optional
 583:         if ($rest->size >= 0 && ($rest->fp !== false || $rest->data !== false))
 584:         {
 585:             $rest->setHeader('Content-Type', $input['type']);
 586:             if (isset($input['md5sum'])) $rest->setHeader('Content-MD5', $input['md5sum']);
 587: 
 588:             foreach ($metaHeaders as $h => $v) $rest->setSwsHeader('x-sws-object-meta-'.$h, $v);
 589:             $rest->getResponse();
 590:         } else
 591:             $rest->response->error = array('code' => 0, 'message' => 'Missing input parameters');
 592: 
 593:         if ($rest->response->error === false && $rest->response->code !== 201)
 594:             $rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
 595:         if ($rest->response->error !== false)
 596:         {
 597:             self::__triggerError(sprintf("Storage::putObject(): [%s] %s",
 598:                 $rest->response->error['code'], $rest->response->error['message']), __FILE__, __LINE__);
 599:             return false;
 600:         }
 601:         return true;
 602:     }
 603: 
 604: 
 605:     /**
 606:      * Put an object from a file (legacy function)
 607:      *
 608:      * @param string $file Input file path
 609:      * @param string $bucket Bucket name
 610:      * @param string $uri Object URI
 611:      * @param constant $acl ACL constant
 612:      * @param array $metaHeaders Array of x-meta-* headers
 613:      * @param string $contentType Content type
 614:      * @return boolean
 615:      */
 616:     public static function putObjectFile($file, $bucket, $uri, $metaHeaders = array(), $contentType = null)
 617:     {
 618:         return self::putObject(self::inputFile($file), $bucket, $uri, $metaHeaders, $contentType);
 619:     }
 620: 
 621: 
 622:     /**
 623:      * Put an object from a string (legacy function)
 624:      *
 625:      * @param string $string Input data
 626:      * @param string $bucket Bucket name
 627:      * @param string $uri Object URI
 628:      * @param constant $acl ACL constant
 629:      * @param array $metaHeaders Array of x-sws-meta-* headers
 630:      * @param string $contentType Content type
 631:      * @return boolean
 632:      */
 633:     public static function putObjectString($string, $bucket, $uri, $metaHeaders = array(), $contentType = 'text/plain')
 634:     {
 635:         return self::putObject($string, $bucket, $uri, $metaHeaders, $contentType);
 636:     }
 637: 
 638:     /**
 639:      * 修改一个Object的属性
 640:      *
 641:      * @param string $bucket Bucket名称
 642:      * @param constant $uri Object名称
 643:      * @param array $metaHeaders x-sws-container-meta-* header数组
 644:      * @param array $requestHeaders 其它header属性
 645:      * @return boolean
 646:      */
 647:     public static function postObject($bucket, $uri, $metaHeaders=array(), $requestHeaders=Array())
 648:     {
 649:         $rest = new StorageRequest('POST', self::$__account, $bucket, $uri, self::$endpoint);
 650:         foreach ($metaHeaders as $k => $v) {
 651:             $rest->setSwsHeader('x-sws-object-meta-'.$k, $v);
 652:         }
 653:         foreach ($requestHeaders as $k => $v) {
 654:             $rest->setHeader('x-sws-object-meta-'.$k, $v);
 655:         }
 656: 
 657:         $rest = $rest->getResponse();
 658: 
 659:         if ($rest->error === false && ($rest->code !== 202))
 660:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 661:         if ($rest->error !== false)
 662:         {
 663:             self::__triggerError(sprintf("Storage::postObject({$bucket}, {$uri}): [%s] %s",
 664:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 665:             return false;
 666:         }
 667:         return true;
 668:     }
 669: 
 670:     /**
 671:      * 获取一个Object的内容
 672:      *
 673:      * @param string $bucket Bucket名称
 674:      * @param string $uri Object名称
 675:      * @param mixed $saveTo 文件保存到的文件名或者句柄
 676:      * @return mixed 返回服务端返回的response，其中headers为Object的属性信息，body为Object的内容
 677:      */
 678:     public static function getObject($bucket, $uri, $saveTo = false)
 679:     {
 680:         $rest = new StorageRequest('GET', self::$__account, $bucket, $uri, self::$endpoint);
 681:         if ($saveTo !== false)
 682:         {
 683:             if (is_resource($saveTo))
 684:                 $rest->fp =& $saveTo;
 685:             else
 686:                 if (($rest->fp = @fopen($saveTo, 'wb')) !== false)
 687:                     $rest->file = realpath($saveTo);
 688:                 else
 689:                     $rest->response->error = array('code' => 0, 'message' => 'Unable to open save file for writing: '.$saveTo);
 690:         }
 691:         if ($rest->response->error === false) $rest->getResponse();
 692: 
 693:         if ($rest->response->error === false && $rest->response->code !== 200)
 694:             $rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
 695:         if ($rest->response->error !== false)
 696:         {
 697:             self::__triggerError(sprintf("Storage::getObject({$bucket}, {$uri}): [%s] %s",
 698:                 $rest->response->error['code'], $rest->response->error['message']), __FILE__, __LINE__);
 699:             return false;
 700:         }
 701:         return $rest->response;
 702:     }
 703: 
 704: 
 705:     /**
 706:      * 获取一个Object的信息
 707:      *
 708:      * @param string $bucket Bucket名称
 709:      * @param string $uri Object名称
 710:      * @param boolean $returnInfo 是否返回Object的详细信息
 711:      * @return mixed | false
 712:      */
 713:     public static function getObjectInfo($bucket, $uri, $returnInfo = true)
 714:     {
 715:         $rest = new StorageRequest('HEAD', self::$__account, $bucket, $uri, self::$endpoint);
 716:         $rest = $rest->getResponse();
 717:         if ($rest->error === false && ($rest->code !== 200 && $rest->code !== 404))
 718:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 719:         if ($rest->error !== false)
 720:         {
 721:             self::__triggerError(sprintf("Storage::getObjectInfo({$bucket}, {$uri}): [%s] %s",
 722:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 723:             return false;
 724:         }
 725:         return $rest->code == 200 ? $returnInfo ? $rest->headers : true : false;
 726:     }
 727: 
 728: 
 729:     /**
 730:      * 从一个Bucket复制一个Object到另一个Bucket
 731:      *
 732:      * @param string $srcBucket 源Bucket名称
 733:      * @param string $srcUri 源Object名称
 734:      * @param string $bucket 目标Bucket名称
 735:      * @param string $uri 目标Object名称
 736:      * @param array $metaHeaders Optional array of x-sws-meta-* headers
 737:      * @param array $requestHeaders Optional array of request headers (content type, disposition, etc.)
 738:      * @return mixed | false
 739:      */
 740:     public static function copyObject($srcBucket, $srcUri, $bucket, $uri, $metaHeaders = array(), $requestHeaders = array())
 741:     {
 742:         $rest = new StorageRequest('PUT', self::$__account, $bucket, $uri, self::$endpoint);
 743:         $rest->setHeader('Content-Length', 0);
 744:         foreach ($requestHeaders as $h => $v)
 745:             strpos($h, 'x-sws-') === 0 ? $rest->setSwsHeader($h, $v) : $rest->setHeader($h, $v);
 746:         foreach ($metaHeaders as $h => $v) $rest->setSwsHeader('x-sws-object-meta-'.$h, $v);
 747:         $rest->setSwsHeader('x-sws-copy-from', sprintf('/%s/%s', $srcBucket, rawurlencode($srcUri)));
 748: 
 749:         $rest = $rest->getResponse();
 750:         if ($rest->error === false && $rest->code !== 201)
 751:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 752:         if ($rest->error !== false)
 753:         {
 754:             self::__triggerError(sprintf("Storage::copyObject({$srcBucket}, {$srcUri}, {$bucket}, {$uri}): [%s] %s",
 755:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 756:             return false;
 757:         }
 758:         return isset($rest->body->LastModified, $rest->body->ETag) ? array(
 759:             'time' => strtotime((string)$rest->body->LastModified),
 760:             'hash' => substr((string)$rest->body->ETag, 1, -1)
 761:         ) : false;
 762:     }
 763: 
 764: 
 765:     /**
 766:      * Set object or bucket Access Control Policy
 767:      *
 768:      * @param string $bucket Bucket name
 769:      * @param string $uri Object URI
 770:      * @param array $acp Access Control Policy Data (same as the data returned from getAccessControlPolicy)
 771:      * @return boolean
 772:      */
 773:     public static function setAccessControlPolicy($bucket, $uri = '', $acp = array())
 774:     {
 775:     }
 776: 
 777: 
 778:     /**
 779:      * 删除一个Object
 780:      *
 781:      * @param string $bucket Bucket名称
 782:      * @param string $uri Object名称
 783:      * @return boolean
 784:      */
 785:     public static function deleteObject($bucket, $uri)
 786:     {
 787:         $rest = new StorageRequest('DELETE', self::$__account, $bucket, $uri, self::$endpoint);
 788:         $rest = $rest->getResponse();
 789:         if ($rest->error === false && $rest->code !== 204)
 790:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 791:         if ($rest->error !== false)
 792:         {
 793:             self::__triggerError(sprintf("Storage::deleteObject(): [%s] %s",
 794:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 795:             return false;
 796:         }
 797:         return true;
 798:     }
 799: 
 800: 
 801:     /**
 802:      * 获取一个Object的外网直接访问URL
 803:      *
 804:      * @param string $bucket Bucket名称
 805:      * @param string $uri Object名称
 806:      * @return string
 807:      */
 808:     public static function getUrl($bucket, $uri)
 809:     {
 810:         return "http://" . self::$__account . '-' . $bucket . '.stor.sinaapp.com/' . rawurlencode($uri);
 811:     }
 812: 
 813:      /**
 814:      * 获取一个Object的外网临时访问URL
 815:      *
 816:      * @param string $bucket Bucket名称
 817:      * @param string $uri Object名称
 818:      * @param string $method Http请求的方法，有GET, PUT, DELETE等
 819:      * @param int    $seconds 设置这个此URL的过期时间，单位是秒
 820:      */
 821:     public static function getTempUrl($bucket, $uri, $method, $seconds) 
 822:     {
 823:         $expires = (int)(time() + $seconds);
 824:         $path = "/v1/SAE_" . self::$__account . "/" . $bucket . "/" . $uri;
 825:         $hmac_body = $method . "\n" . $expires . "\n" . $path;
 826:         $sig = hash_hmac('sha1', $hmac_body, self::$__secretKey);
 827:         $parameter = http_build_query(array("temp_url_sig" => $sig, "temp_url_expires" => $expires));
 828:         return "http://" . self::$__account . '-' . $bucket . '.stor.sinaapp.com/' . rawurlencode($uri) . "?" . $parameter;
 829:     }
 830: 
 831:     /**
 832:      * 获取一个Object的CDN访问URL
 833:      * @param string $bucket Bucket名称
 834:      * @param string $uri Object名称
 835:      * @return string
 836:      */
 837:     public static function getCdnUrl($bucket, $uri)
 838:     {
 839:         return "http://". self::$__account . '.sae.sinacn.com/.app-stor/' . $bucket . '/' . rawurlencode($uri);
 840:     }
 841: 
 842:     /**
 843:      * 修改账户的属性（for internal use onley）
 844:      *
 845:      * @param array $metaHeaders x-sws-account-meta-* header数组
 846:      * @return boolean
 847:      */
 848:     public static function postAccount($metaHeaders=array())
 849:     {
 850:         $rest = new StorageRequest('POST', self::$__account, '', '', self::$endpoint);
 851:         foreach ($metaHeaders as $k => $v) {
 852:             $rest->setSwsHeader('x-sws-account-meta-'.$k, $v);
 853:         }
 854: 
 855:         $rest = $rest->getResponse();
 856: 
 857:         if ($rest->error === false && ($rest->code !== 201 && $rest->code !== 204))
 858:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 859:         if ($rest->error !== false)
 860:         {
 861:             self::__triggerError(sprintf("Storage::postAccount({$bucket}, {$acl}): [%s] %s",
 862:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 863:             return false;
 864:         }
 865:         return true;
 866:     }
 867: 
 868:     /**
 869:      * 获取账户的属性（for internal use only）
 870:      *
 871:      * @param string $bucket Bucket名称
 872:      * @return mixed
 873:      */
 874:     public static function getAccountInfo() {
 875:         $rest = new StorageRequest('HEAD', self::$__account, '', '', self::$endpoint);
 876:         $rest = $rest->getResponse();
 877:         if ($rest->error === false && ($rest->code !== 204 && $rest->code !== 404))
 878:             $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
 879:         if ($rest->error !== false)
 880:         {
 881:             self::__triggerError(sprintf("Storage::getAccountInfo({$bucket}): [%s] %s",
 882:                 $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
 883:             return false;
 884:         }
 885:         return $rest->code !== 404 ? $rest->headers : false;
 886:     }
 887: 
 888: 
 889:     private static function __getMIMEType(&$file)
 890:     {
 891:         static $exts = array(
 892:             'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
 893:             'png' => 'image/png', 'ico' => 'image/x-icon', 'pdf' => 'application/pdf',
 894:             'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'svg' => 'image/svg+xml',
 895:             'svgz' => 'image/svg+xml', 'swf' => 'application/x-shockwave-flash',
 896:             'zip' => 'application/zip', 'gz' => 'application/x-gzip',
 897:             'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
 898:             'bz2' => 'application/x-bzip2',  'rar' => 'application/x-rar-compressed',
 899:             'exe' => 'application/x-msdownload', 'msi' => 'application/x-msdownload',
 900:             'cab' => 'application/vnd.ms-cab-compressed', 'txt' => 'text/plain',
 901:             'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
 902:             'css' => 'text/css', 'js' => 'text/javascript',
 903:             'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
 904:             'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
 905:             'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
 906:             'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
 907:         );
 908: 
 909:         $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
 910:         if (isset($exts[$ext])) return $exts[$ext];
 911: 
 912:         // Use fileinfo if available
 913:         if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
 914:             ($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false)
 915:         {
 916:             if (($type = finfo_file($finfo, $file)) !== false)
 917:             {
 918:                 // Remove the charset and grab the last content-type
 919:                 $type = explode(' ', str_replace('; charset=', ';charset=', $type));
 920:                 $type = array_pop($type);
 921:                 $type = explode(';', $type);
 922:                 $type = trim(array_shift($type));
 923:             }
 924:             finfo_close($finfo);
 925:             if ($type !== false && strlen($type) > 0) return $type;
 926:         }
 927: 
 928:         return 'application/octet-stream';
 929:     }
 930: 
 931: 
 932:     public static function __getTime()
 933:     {
 934:         return time() + self::$__timeOffset;
 935:     }
 936: 
 937: 
 938:     public static function __getSignature($string)
 939:     {
 940:         //var_dump("sign:", $string);
 941:         return 'SWS '.self::$__accessKey.':'.self::__getHash($string);
 942:     }
 943: 
 944: 
 945:     private static function __getHash($string)
 946:     {
 947:         return base64_encode(extension_loaded('hash') ?
 948:             hash_hmac('sha1', $string, self::$__secretKey, true) : pack('H*', sha1(
 949:                 (str_pad(self::$__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
 950:                 pack('H*', sha1((str_pad(self::$__secretKey, 64, chr(0x00)) ^
 951:                 (str_repeat(chr(0x36), 64))) . $string)))));
 952:     }
 953: 
 954: }
 955: 
 956: /**
 957:  * @ignore
 958:  */
 959: final class StorageRequest
 960: {
 961:     private $endpoint;
 962:     private $verb;
 963:     private $uri;
 964:     private $parameters = array();
 965:     private $swsHeaders = array();
 966:     private $headers = array(
 967:         'Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => ''
 968:     );
 969: 
 970:     public $fp = false;
 971:     public $size = 0;
 972:     public $data = false;
 973: 
 974:     public $response;
 975: 
 976: 
 977:     function __construct($verb, $account, $bucket = '', $uri = '', $endpoint = DEFAULT_STORAGE_ENDPOINT)
 978:     {
 979:         $this->endpoint = $endpoint;
 980:         $this->verb = $verb;
 981: 
 982:         $this->uri = "/v1/SAE_" . rawurlencode($account);
 983:         $this->resource = "/$account";
 984:         if ($bucket !== '') {
 985:             $this->uri = $this->uri . '/' . rawurlencode($bucket);
 986:             $this->resource = $this->resource . '/'. $bucket;
 987:         }
 988:         if ($uri !== '') {
 989:             $this->uri .= '/'.str_replace('%2F', '/', rawurlencode($uri));
 990:             $this->resource = $this->resource . '/'. str_replace(' ', '%20', $uri);
 991:         }
 992: 
 993:         $this->headers['Host'] = $this->endpoint;
 994:         $this->headers['Date'] = gmdate('D, d M Y H:i:s T');
 995:         $this->response = new \STDClass;
 996:         $this->response->error = false;
 997:         $this->response->body = null;
 998:         $this->response->headers = array();
 999:     }
1000: 
1001: 
1002:     public function setParameter($key, $value)
1003:     {
1004:         $this->parameters[$key] = $value;
1005:     }
1006: 
1007: 
1008:     public function setHeader($key, $value)
1009:     {
1010:         $this->headers[$key] = $value;
1011:     }
1012: 
1013: 
1014:     public function setSwsHeader($key, $value)
1015:     {
1016:         $this->swsHeaders[$key] = $value;
1017:     }
1018: 
1019: 
1020:     public function getResponse()
1021:     {
1022:         $query = '';
1023:         if (sizeof($this->parameters) > 0)
1024:         {
1025:             $query = substr($this->uri, -1) !== '?' ? '?' : '&';
1026:             foreach ($this->parameters as $var => $value)
1027:                 if ($value == null || $value == '') $query .= $var.'&';
1028:                 else $query .= $var.'='.rawurlencode($value).'&';
1029:             $query = substr($query, 0, -1);
1030:             $this->uri .= $query;
1031:             $this->resource .= $query;
1032:         }
1033:         $url = (Storage::$useSSL ? 'https://' : 'http://') . ($this->headers['Host'] !== '' ? $this->headers['Host'] : $this->endpoint) . $this->uri;
1034: 
1035:         //var_dump('uri: ' . $this->uri, 'url: ' . $url, 'resource: ' . $this->resource);
1036: 
1037:         // Basic setup
1038:         $curl = curl_init();
1039:         curl_setopt($curl, CURLOPT_USERAGENT, 'Storage/php');
1040: 
1041:         if (Storage::$useSSL)
1042:         {
1043:             // Set protocol version
1044:             curl_setopt($curl, CURLOPT_SSLVERSION, Storage::$useSSLVersion);
1045: 
1046:             // SSL Validation can now be optional for those with broken OpenSSL installations
1047:             curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, Storage::$useSSLValidation ? 2 : 0);
1048:             curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, Storage::$useSSLValidation ? 1 : 0);
1049:         }
1050: 
1051:         curl_setopt($curl, CURLOPT_URL, $url);
1052: 
1053:         if (Storage::$proxy != null && isset(Storage::$proxy['host']))
1054:         {
1055:             curl_setopt($curl, CURLOPT_PROXY, Storage::$proxy['host']);
1056:             curl_setopt($curl, CURLOPT_PROXYTYPE, Storage::$proxy['type']);
1057:             if (isset(Storage::$proxy['user'], Storage::$proxy['pass']) && Storage::$proxy['user'] != null && Storage::$proxy['pass'] != null)
1058:                 curl_setopt($curl, CURLOPT_PROXYUSERPWD, sprintf('%s:%s', Storage::$proxy['user'], Storage::$proxy['pass']));
1059:         }
1060: 
1061:         // Headers
1062:         $headers = array(); $sae = array();
1063:         foreach ($this->swsHeaders as $header => $value)
1064:             if (strlen($value) > 0) $headers[] = $header.': '.$value;
1065:         foreach ($this->headers as $header => $value)
1066:             if (strlen($value) > 0) $headers[] = $header.': '.$value;
1067: 
1068:         foreach ($this->swsHeaders as $header => $value)
1069:             if (strlen($value) > 0) $sae[] = strtolower($header).':'.$value;
1070: 
1071:         if (sizeof($sae) > 0)
1072:         {
1073:             usort($sae, array(&$this, '__sortMetaHeadersCmp'));
1074:             $sae= "\n".implode("\n", $sae);
1075:         } else $sae = '';
1076: 
1077:         if (Storage::hasAuth())
1078:         {
1079:             $headers[] = 'Authorization: ' . Storage::__getSignature(
1080:                 $this->verb."\n".
1081:                 $this->headers['Date'].$sae."\n".
1082:                 $this->resource
1083:             );
1084:         }
1085: 
1086:         //var_dump("headers:", $headers);
1087: 
1088:         curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
1089:         curl_setopt($curl, CURLOPT_HEADER, false);
1090:         curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
1091:         curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
1092:         curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, '__responseHeaderCallback'));
1093:         curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
1094: 
1095:         // Request types
1096:         switch ($this->verb)
1097:         {
1098:         case 'GET': break;
1099:         case 'PUT': case 'POST':
1100:             if ($this->fp !== false)
1101:             {
1102:                 curl_setopt($curl, CURLOPT_PUT, true);
1103:                 curl_setopt($curl, CURLOPT_INFILE, $this->fp);
1104:                 if ($this->size >= 0)
1105:                     curl_setopt($curl, CURLOPT_INFILESIZE, $this->size);
1106:             }
1107:             elseif ($this->data !== false)
1108:             {
1109:                 curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
1110:                 curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
1111:             }
1112:             else
1113:                 curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
1114:             break;
1115:         case 'HEAD':
1116:             curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
1117:             curl_setopt($curl, CURLOPT_NOBODY, true);
1118:             break;
1119:         case 'DELETE':
1120:             curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
1121:             break;
1122:         default: break;
1123:         }
1124: 
1125:         // Execute, grab errors
1126:         if (curl_exec($curl))
1127:             $this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
1128:         else
1129:             $this->response->error = array(
1130:                 'code' => curl_errno($curl),
1131:                 'message' => curl_error($curl),
1132:             );
1133: 
1134:         @curl_close($curl);
1135: 
1136:         // Clean up file resources
1137:         if ($this->fp !== false && is_resource($this->fp)) fclose($this->fp);
1138: 
1139:         //var_dump("response:", $this->response);
1140:         return $this->response;
1141:     }
1142: 
1143: 
1144:     private function __sortMetaHeadersCmp($a, $b)
1145:     {
1146:         $lenA = strpos($a, ':');
1147:         $lenB = strpos($b, ':');
1148:         $minLen = min($lenA, $lenB);
1149:         $ncmp = strncmp($a, $b, $minLen);
1150:         if ($lenA == $lenB) return $ncmp;
1151:         if (0 == $ncmp) return $lenA < $lenB ? -1 : 1;
1152:         return $ncmp;
1153:     }
1154: 
1155: 
1156:     private function __responseWriteCallback(&$curl, &$data)
1157:     {
1158:         if (in_array($this->response->code, array(200, 206)) && $this->fp !== false)
1159:             return fwrite($this->fp, $data);
1160:         else
1161:             $this->response->body .= $data;
1162:         return strlen($data);
1163:     }
1164: 
1165: 
1166:     private function __responseHeaderCallback($curl, $data)
1167:     {
1168:         if (($strlen = strlen($data)) <= 2) return $strlen;
1169:         if (substr($data, 0, 4) == 'HTTP')
1170:             $this->response->code = (int)substr($data, 9, 3);
1171:         else
1172:         {
1173:             $data = trim($data);
1174:             if (strpos($data, ': ') === false) return $strlen;
1175:             list($header, $value) = explode(': ', $data, 2);
1176:             if ($header == 'Last-Modified')
1177:                 $this->response->headers['time'] = strtotime($value);
1178:             elseif ($header == 'Date')
1179:                 $this->response->headers['date'] = strtotime($value);
1180:             elseif ($header == 'Content-Length')
1181:                 $this->response->headers['size'] = (int)$value;
1182:             elseif ($header == 'Content-Type')
1183:                 $this->response->headers['type'] = $value;
1184:             elseif ($header == 'ETag')
1185:                 $this->response->headers['hash'] = $value{0} == '"' ? substr($value, 1, -1) : $value;
1186:             elseif (preg_match('/^x-sws-(?:account|container|object)-read$/i', $header))
1187:                 $this->response->headers['acl'] = $value;
1188:             elseif (preg_match('/^x-sws-(?:account|container|object)-meta-(.*)$/i', $header))
1189:                 $this->response->headers[strtolower($header)] = $value;
1190:             elseif (preg_match('/^x-sws-(?:account|container|object)-(.*)$/i', $header, $m))
1191:                 $this->response->headers[strtolower($m[1])] = $value;
1192:         }
1193:         return $strlen;
1194:     }
1195: 
1196: }
1197: 
1198: /**
1199:  * Storage异常类
1200:  */
1201: class StorageException extends \Exception {
1202:     /**
1203:      * 构造函数
1204:      *
1205:      * @param string $message 异常信息
1206:      * @param string $file 抛出异常的文件
1207:      * @param string $line 抛出异常的代码行
1208:      * @param int $code 异常码
1209:      */
1210:     function __construct($message, $file, $line, $code = 0)
1211:     {
1212:         parent::__construct($message, $code);
1213:         $this->file = $file;
1214:         $this->line = $line;
1215:     }
1216: }
1217: 
1218: /**
1219:  * A PHP wrapper for Storage
1220:  *
1221:  * @ignore
1222:  */
1223: final class StorageWrapper extends Storage {
1224:     private $position = 0, $mode = '', $buffer;
1225: 
1226:     public function url_stat($path, $flags) {
1227:         self::__getURL($path);
1228:         return (($info = self::getObjectInfo($this->url['host'], $this->url['path'])) !== false) ?
1229:             array('size' => $info['size'], 'mtime' => $info['time'], 'ctime' => $info['time']) : false;
1230:     }
1231: 
1232:     public function unlink($path) {
1233:         self::__getURL($path);
1234:         return self::deleteObject($this->url['host'], $this->url['path']);
1235:     }
1236: 
1237:     public function mkdir($path, $mode, $options) {
1238:         self::__getURL($path);
1239:         return self::putBucket($this->url['host'], self::__translateMode($mode));
1240:     }
1241: 
1242:     public function rmdir($path) {
1243:         self::__getURL($path);
1244:         return self::deleteBucket($this->url['host']);
1245:     }
1246: 
1247:     public function dir_opendir($path, $options) {
1248:         self::__getURL($path);
1249:         if (($contents = self::getBucket($this->url['host'], $this->url['path'])) !== false) {
1250:             $pathlen = strlen($this->url['path']);
1251:             if (substr($this->url['path'], -1) == '/') $pathlen++;
1252:             $this->buffer = array();
1253:             foreach ($contents as $file) {
1254:                 if ($pathlen > 0) $file['name'] = substr($file['name'], $pathlen);
1255:                 $this->buffer[] = $file;
1256:             }
1257:             return true;
1258:         }
1259:         return false;
1260:     }
1261: 
1262:     public function dir_readdir() {
1263:         return (isset($this->buffer[$this->position])) ? $this->buffer[$this->position++]['name'] : false;
1264:     }
1265: 
1266:     public function dir_rewinddir() {
1267:         $this->position = 0;
1268:     }
1269: 
1270:     public function dir_closedir() {
1271:         $this->position = 0;
1272:         unset($this->buffer);
1273:     }
1274: 
1275:     public function stream_close() {
1276:         if ($this->mode == 'w') {
1277:             self::putObject($this->buffer, $this->url['host'], $this->url['path']);
1278:         }
1279:         $this->position = 0;
1280:         unset($this->buffer);
1281:     }
1282: 
1283:     public function stream_stat() {
1284:         if (is_object($this->buffer) && isset($this->buffer->headers))
1285:             return array(
1286:                 'size' => $this->buffer->headers['size'],
1287:                 'mtime' => $this->buffer->headers['time'],
1288:                 'ctime' => $this->buffer->headers['time']
1289:             );
1290:         elseif (($info = self::getObjectInfo($this->url['host'], $this->url['path'])) !== false)
1291:             return array('size' => $info['size'], 'mtime' => $info['time'], 'ctime' => $info['time']);
1292:         return false;
1293:     }
1294: 
1295:     public function stream_flush() {
1296:         $this->position = 0;
1297:         return true;
1298:     }
1299: 
1300:     public function stream_open($path, $mode, $options, &$opened_path) {
1301:         if (!in_array($mode, array('r', 'rb', 'w', 'wb'))) return false; // Mode not supported
1302:         $this->mode = substr($mode, 0, 1);
1303:         self::__getURL($path);
1304:         $this->position = 0;
1305:         if ($this->mode == 'r') {
1306:             if (($this->buffer = self::getObject($this->url['host'], $this->url['path'])) !== false) {
1307:                 if (is_object($this->buffer->body)) $this->buffer->body = (string)$this->buffer->body;
1308:             } else return false;
1309:         }
1310:         return true;
1311:     }
1312: 
1313:     public function stream_read($count) {
1314:         if ($this->mode !== 'r' && $this->buffer !== false) return false;
1315:         $data = substr(is_object($this->buffer) ? $this->buffer->body : $this->buffer, $this->position, $count);
1316:         $this->position += strlen($data);
1317:         return $data;
1318:     }
1319: 
1320:     public function stream_write($data) {
1321:         if ($this->mode !== 'w') return 0;
1322:         $left = substr($this->buffer, 0, $this->position);
1323:         $right = substr($this->buffer, $this->position + strlen($data));
1324:         $this->buffer = $left . $data . $right;
1325:         $this->position += strlen($data);
1326:         return strlen($data);
1327:     }
1328: 
1329:     public function stream_tell() {
1330:         return $this->position;
1331:     }
1332: 
1333:     public function stream_eof() {
1334:         return $this->position >= strlen(is_object($this->buffer) ? $this->buffer->body : $this->buffer);
1335:     }
1336: 
1337:     public function stream_seek($offset, $whence) {
1338:         switch ($whence) {
1339:         case SEEK_SET:
1340:             if ($offset < strlen($this->buffer->body) && $offset >= 0) {
1341:                 $this->position = $offset;
1342:                 return true;
1343:             } else return false;
1344:             break;
1345:         case SEEK_CUR:
1346:             if ($offset >= 0) {
1347:                 $this->position += $offset;
1348:                 return true;
1349:             } else return false;
1350:             break;
1351:         case SEEK_END:
1352:             $bytes = strlen($this->buffer->body);
1353:             if ($bytes + $offset >= 0) {
1354:                 $this->position = $bytes + $offset;
1355:                 return true;
1356:             } else return false;
1357:             break;
1358:         default: return false;
1359:         }
1360:     }
1361: 
1362:     private function __getURL($path) {
1363:         $this->url = parse_url($path);
1364:         if (!isset($this->url['scheme']) || $this->url['scheme'] !== 'storage') return $this->url;
1365:         if (isset($this->url['user'], $this->url['pass'])) self::setAuth($this->url['user'], $this->url['pass']);
1366:         $this->url['path'] = isset($this->url['path']) ? substr($this->url['path'], 1) : '';
1367:     }
1368: 
1369:     private function __translateMode($mode) {
1370:         $acl = self::ACL_PRIVATE;
1371:         if (($mode & 0x0020) || ($mode & 0x0004))
1372:             $acl = self::ACL_PUBLIC_READ;
1373:         // You probably don't want to enable public write access
1374:         if (($mode & 0x0010) || ($mode & 0x0008) || ($mode & 0x0002) || ($mode & 0x0001))
1375:             $acl = self::ACL_PUBLIC_READ; //$acl = self::ACL_PUBLIC_READ_WRITE;
1376:         return $acl;
1377:     }
1378: } stream_wrapper_register('storage', 'sinacloud\sae\StorageWrapper');
1379: 