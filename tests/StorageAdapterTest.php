<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Litp\Flysystem\StorageAdapter;
use Litp\Flysystem\Storage;
use League\Flysystem\Filesystem;

class StorageAdapterTest extends PHPUnit_Framework_TestCase
{
	protected $storage;
	protected $adapter;
	protected $filesystem;

	/**
	* @before
	*/
	public function setup()
	{
		// load dotenv file
		$dotenv = new Dotenv\Dotenv(__DIR__ . "/../");
		$dotenv->load();
		
		// create filesystem using the instance of StorageAdapter
		$this->storage = new Storage(getenv('APPNAME') . ':' . getenv('ACCESSKEY'),getenv('SECRETKEY'));
		$this->adapter = new StorageAdapter($this->storage, getenv('BUCKET'));
		$this->filesystem = new Filesystem($this->adapter);	

		// create necessary files at SAE Storage
		if(!$this->filesystem->has('test/test.txt')){
			$this->filesystem->write('test/test.txt','This is a test text file');
		}
		if(!$this->filesystem->has('test/dir/a.txt')){
			$this->filesystem->write('test/dir/a.txt','This is a test text file named a.txt');
		}
		if(!$this->filesystem->has('test/rename.txt')){
			$this->filesystem->write('test/rename.txt','This is a test text file named a.txt');
		}
		if(!$this->filesystem->has('test/del.txt')){
			$this->filesystem->write('test/del.txt','This is a test text file named a.txt');
		}
		if(!$this->filesystem->has('test/update.txt')){
			$this->filesystem->write('test/update.txt','This is a test text file named a.txt');
		}
	}

	// if setUP goes well, this assert should be successfull
	public function testSetting()
	{
		$this->assertTrue(true);	
	}

	public function testHas()
	{
		$r1 = $this->filesystem->has('test/test.txt');
		$r2 = $this->filesystem->has('test/test2.txt');
		$this->assertTrue($r1);
		$this->assertFalse($r2);
	}

	public function testRead()
	{
		$r1 = $this->filesystem->read('test/test.txt');
		$this->assertEquals('This is a test text file',$r1);
	}

	public function testListContents()
	{

	}

	public function testUpdate()
	{
		$this->filesystem->update('test/update.txt','updated');
		$r = $this->filesystem->read('test/update.txt');
		$this->assertEquals('updated',$r);
	}

	public function testDelete()
	{
		$this->assertTrue($this->filesystem->has('test/del.txt'));
		$this->filesystem->delete('test/del.txt');
		$this->assertFalse($this->filesystem->has('test/del.txt'));
	}

	public function testCopy()
	{
		$this->assertFalse($this->filesystem->has('test/copy.txt'));
		$this->filesystem->copy('test/test.txt','test/copy.txt');
		$this->assertEquals($this->filesystem->read('test/test.txt'),$this->filesystem->read('test/copy.txt'));
	}

	public function testRename()
	{
		$this->filesystem->rename('test/rename.txt','test/rename_new.txt');
		$this->assertTrue($this->filesystem->has('test/rename_new.txt'));
		$this->assertFalse($this->filesystem->has('test/rename.txt'));
	}

	/**
	* @after
	*/
	public function teardown()
	{
		// delete all the files created in setUp()
		if($this->filesystem->has('test/test.txt')){
			$this->filesystem->delete('test/test.txt','This is a test text file');
		}
		if($this->filesystem->has('test/dir/a.txt')){
			$this->filesystem->delete('test/dir/a.txt','This is a test text file named a.txt');
		}
		if($this->filesystem->has('test/rename.txt')){
			$this->filesystem->delete('test/rename.txt','This is a test text file named a.txt');
		}
		if($this->filesystem->has('test/del.txt')){
			$this->filesystem->delete('test/del.txt','This is a test text file named a.txt');
		}
		if($this->filesystem->has('test/update.txt')){
			$this->filesystem->delete('test/update.txt','This is a test text file named a.txt');
		}
		if($this->filesystem->has('test/copy.txt')){
			$this->filesystem->delete('test/copy.txt','This is a test text file named a.txt');
		}
		if($this->filesystem->has('test/rename_new.txt')){
			$this->filesystem->delete('test/rename_new.txt','This is a test text file named a.txt');
		}
	}
}

