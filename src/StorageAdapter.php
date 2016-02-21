<?php

namespace Litp\Flysystem;


use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;

class StorageAdapter extends AbstractAdapter 
{

    protected $storage;
    protected $bucket;

    public function __construct(Storage $client, $bucket)
    {
        $this->storage = $client;
        $this->bucket = $bucket;
    }

    
    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        return $this->storage->getObjectInfo($this->bucket,$path,false);
    }


    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        $response = $this->storage->getObject($this->bucket,$path);
        if ($response->error != true){
            $content['contents'] = $response->body;
            return $content;
        } else {
            return false;
        }
    }


    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        $response = $this->storage->getObject($this->bucket,$path);
        if ($response->error != true){
            $content['stream'] = $response->body;
            return $contents;
        } else {
            return false;
        }        
    }


    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $results = [];

        if($directory!=''){
            if(substr($directory, -1)!='/'){
                $directory = $directory . '/';
            }
        }

        if ($recursive == false){
            $dirs = $this->storage->getBucket($this->bucket, $directory, null, 10000, '/');
            foreach($dirs as $dir){
                if(!array_key_exists('subdir', $dir)){
                array_push($results,Util::map($dir,['content_type'=>'type','name'=>'path','last_modified'=>'timestamp','bytes'=>'size']));
                }
            }
            return $results;
        } else {
            $dirs = $this->storage->getBucket($this->bucket, $directory, null, 10000, '/');
            foreach($dirs as $dir){
                if(!array_key_exists('subdir', $dir)){
                    array_push($results,Util::map($dir,['content_type'=>'type','name'=>'path','last_modified'=>'timestamp','bytes'=>'size']));
                } else {
                    $results = array_merge($results,$this->listContents($dir['subdir'],true));
                }
            }
            return $results;
        }
    }


    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        return $this->storage->getObjectInfo($this->bucket,$path,true);
    }


    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        $result = $this->getObjectInfo($this->bucket,$path,true);
        return $result['size'];
    }



    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        $result = $this->getObjectInfo($this->bucket,$path,true);
        return $result['type'];
    }



    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $result = $this->getObjectInfo($this->bucket,$path,true);
        return $result['date'];
    }



    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return true;
    }


    /////////////////////////////////////////////////////
        /**
     * @const  VISIBILITY_PUBLIC  public visibility
     */
    const VISIBILITY_PUBLIC = 'public';
    /**
     * @const  VISIBILITY_PRIVATE  private visibility
     */
    const VISIBILITY_PRIVATE = 'private';


    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        if((bool) $this->storage->putObject($contents, $this->bucket, $path)){
            return $this->getMetadata($path);
        } else {
            return false;
        }
    }


    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        if((bool) $this->storage->putObject($contents, $this->bucket, $path)){
            return $this->getMetadata($path);
        } else {
            return false;
        }
    }



    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        if ( ! $this->has($path)){
            return false;
        } else {
            return $this->write($path, $contents, $config);
        }
    }



    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        if ( ! $this->has($path)){
            return false;
        } else {
            return $this->write($path, $resource, $config);
        }        
    }


    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        $r1 = $this->copy($path, $newpath);
        $r2 = $this->delete($path);
        return $r1 && $r2;
    }



    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        if( !$this->has($path)){
            return false;
        } else {
            return (bool) !$this->storage->copyObject($this->bucket,$path,$this->bucket,$newpath);
        }
    }


    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        if(!$this->has($path)){
            return false;
        } else {
            return (bool) $this->storage->deleteObject($this->bucket,$path);
        }
    }


    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        foreach ($this->storage->listContents($dirname,ture) as $obj){
            $this->delete($obj['path']);
        }
        return true;
    }


    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        $this->storage->write($dirname,'',$config);
    }


    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        return true;
    }

   }
