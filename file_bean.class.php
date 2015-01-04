<?php


/**
 * Serves as a cacheable information container that has its own behaviour to determine the caching policy related to it.
 * For a more complete security handling regarding caching policy, I suggest to create a file containing all the mime-type
 * caching policies. If needed, this class can be extended to implement a more complex 
 */
class FileBean{
    const APACHE_MIME_TYPES_URL = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
    
    protected static $mime_types;
    
    protected $filepath;
    protected $extension;
    protected $memcache_time;
    protected $mime;
    protected $cache_script_enabled;
    protected $last_modified;
    protected $eTag;
    protected $expires;
    protected $max_age;
    protected $control;
    protected $lang;
    
    public function __construct
    ($path, $memcache_time, $ext_array = null, $eTag = null, $expires = null, $max_age = null, $control = null, $lang = null)
    {
        $this->filepath = $path;
        $this->last_modified = new DateTime('@' . (string)filemtime($path));
        $this->extension = pathinfo($path, PATHINFO_EXTENSION);
        $this->memcache_time = $memcache_time;
        $this->cache_script_enabled = static::set_cache_script($this->extension, $ext_array);
        if($this->cache_script_enabled){
            $this->mime = static::get_mime_type($this->extension);
            $this->eTag = $eTag;
            $this->expires = $expires;
            $this->max_age = $max_age;
            $this->control = $control;
            $this->lang = $lang;
        }
    }
    
    /*
     * Sets if the file should be cached on the client side or not, based on the extension.
     */
    protected static function set_cache_script($extension, $ext_array = null){
        if(isset($ext_array)){
            return in_array($extension, $ext_array);
        }else{
            $ext_array = ["css", "html", "gif", "jpg", "jpeg", "js", "png", "xlsx", "xml"];
            return in_array($extension, $ext_array);
        }
    }
    
    public function get_path(){
        return $this->filepath;
    }
    
    public function is_cache_script_enabled(){
        return $this->cache_script_enabled;
    }
    
    public function get_header_fields(){
        return [
                    'mime' => $this->mime,
                    'max_age' => $this->max_age,
                    'last_modified' => $this->last_modified,
                    'etag' => $this->eTag,
                    'expires' => $this->expires,
                    'control' => $this->control,
                    'lang' => $this->lang
               ];
    }
    
    public function get_memcache_expires(){
        return $this->memcache_time;
    }
    
    public function set_last_modified($last_modified){
        $this->last_modified = $last_modified;
    }
    
    /**
     * Returns the mime_type associated with the extension. 
     * If extension is unrecognized, returns text/plain as default
     * @param string $ext
     */
    protected static function get_mime_type($ext){
        if(!isset(static::$mime_types)){
            $mime_types = CacheService::getFromMemcache('mime_array');
            if($mime_types === false){
                $mime_types = generateUpToDateMimeArray();
                if($mime_types === false){
                    
                }
                CacheService::setToMemcache($mime_types, 'mime_array', 3600*24*365);
            }
            static::$mime_types = $mime_types;
        }
        return isset(static::$mime_types[$ext]) ? static::$mime_types[$ext] : 'text/plain';
    }
    
    /**
     * Generates an up-to-date Mime Array from the apache standards.
     * Source: http://php.net/manual/en/function.mime-content-type.php#107798
     * @throws Exception Throws an exception if the Apache Mime Type URL is not valid.
     * @return array
     */
    public static function generateUpToDateMimeArray(){
        $s= [];
        foreach(@explode("\n",@file_get_contents(APACHE_MIME_TYPES_URL))as $x){
            if(isset($x[0])&&$x[0]!=='#'&&preg_match_all('#([^\s]+)#',$x,$out)&&isset($out[1])&&($c=count($out[1]))>1){
                for($i=1;$i<$c;$i++){
                    $s[$out[1][$i]] = $out[1][0];
                }
            }
        }
        if(empty($s)){
            throw new Exception('Apache Mime Type Script failed.');
        }
        return $s;
    }
    
}
