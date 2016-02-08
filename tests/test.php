<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Litp\Flysystem\StorageAdapter;
use Litp\Flysystem\Storage;
use League\Flysystem\Filesystem;

function test_assert($var, $expect, $test_name)
{
	if ($var == $expect){
		echo $test_name . " tested successfully <br>";
	} else {
		echo $test_name . " tested failed <br>";
	}
}

// setup for testing
$storage = new Storage("kvdbtest:on3zyxly0n","wxzz5i30hkzk5j1j32w13klk00ih223wyxykjxzw");
$adapter = new StorageAdapter($storage,'test');
$filesystem = new Filesystem($adapter);

// test for ::has()
if(!$filesystem->has('test/test.txt')){
	$filesystem->write('test/test.txt','This is a test text file');
}
if(!$filesystem->has('test/dir/a.txt')){
	$filesystem->write('test/dir/a.txt','This is a test text file named a.txt');
}
if(!$filesystem->has('test/rename.txt')){
	$filesystem->write('test/rename.txt','This is a test text file named a.txt');
}
if(!$filesystem->has('test/del.txt')){
	$filesystem->write('test/del.txt','This is a test text file named a.txt');
}

$r = $filesystem->has('test/test.txt');
test_assert($r,true,"has()");
$r = $filesystem->has('test/test2.txt');
test_assert($r,false,"has()");

// test for ::read()
$r = $filesystem->read('test/test.txt');
test_assert($r,'This is a test text file', 'read()');

// test for ::listContents


$r = $filesystem->listContents('test/',false);
//var_dump($r);
$r = $filesystem->listContents('test/',true);
//var_dump($r);

// test for ::write()
test_assert(1,1,'write()');

// test for ::getMetaData()

// test for ::getSize

// test for ::getMimetype

// test for ::getTimestamp

// test for ::getVisibility

// test for ::update
$filesystem->update('test/dir/a.txt','The updated content');
$r = $filesystem->read('test/dir/a.txt');
test_assert($r,'The updated content','update()');

// test for delete
if($filesystem->has('test/del.txt')){
    $filesystem->delete('test/del.txt');
    test_assert($filesystem->has('test/del.txt'),false,'delete()');
} else {
    echo "Creating file failed<br>";
}

// test for ::copy
$filesystem->copy('test/test.txt','test/test_copy.txt');
test_assert($filesystem->read('test/test_copy.txt'),'This is a test text file','copy()');
$r = $filesystem->delete('test/test_copy.txt');


// test for ::rename
$filesystem->rename('test/rename.txt','test/newname3.txt');
$r = $filesystem->has('test/newname3.txt');
test_assert($r,true,'Rename()');
test_assert($filesystem->has('test/rename.txt'),false,'Rename() after');
$filesystem->rename('test/newname3.txt','test/rename.txt');

