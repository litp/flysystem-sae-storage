<?php

namespace Litp\Flysystem;

use sinacloud\sae\Storage as Client;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;

class SAEStorageAdapter extends AbstractAdapter 
{

    protected $storage;
    protected $bucket;

    public function __construct(Client $client, $bucket)
    {
        $this->$storage = $client;
        $this->$bucket = $bucket;
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
        return $this->$storage->getObjectInfo($this->$bucket,$path,false);
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
        $response = $this->$storage->getObject($this->$bucket,$path);
        if ($response == 200){
            $content['contents'] = $response->body;
            return $contents;
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
        $response = $this->$storage->getObject($this->$bucket,$path);
        if ($response == 200){
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
        if ($recursive == false){
            return $this->$storage->getBucket($this->$bucket, $path, null, 10000, '/');
        } else {
            return $this->$storage->getBucket($this->$bucket, $path, null, 10000);
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
        //
    }



    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path);
    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path);
    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path);


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
        return (bool) $this->$storage->putObject($contents, $this->$bucket, $path);
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
        return (bool) $this->$storage->putObject($resource, $this->$bucket, $path);
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
        return $this->copy($path, $newpath) && $this->delete($path);
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
            return (bool) $this->$storage->copyObject($this->$bucket,$path,$this->$bucket,$newpath);
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
            return (bool) $this->$storage->deleteObject($this->$bucket,$path);
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
        if(!$this->has($dirname)){
            return false;
        } else{
            foreach ($this->$storage->listContents($dirname,ture) as $obj){
                $this->$storage->delete($obj);
            }
            return true;
        }
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
        $this->$storage->write($dirname,'',$config);
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

    }

   }