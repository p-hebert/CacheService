<?php
require_once('lib/phpfastcache/phpfastcache.php');

/**
 * Centralizes all the cache services, both client and server side.
 */
class CacheService {
    
    /**
     * This function sends the minimum headers. You can add any other you need like Accept Encoding or Vary
     * You don't need to use both ETag and Last-Modified, nor both Expires and max-age.
     * @param string $eTag hash of the information of interest for validation
     * @param DateTime $last_modified When the file was last modified
     * @param DateTime $expires Date when the cache expires 
     * @param string $control Manages CDN behaviours, can have "no-cache", "no-store", "private" or "public".
     * @param int $max_age Time in seconds before the data expires.
     * @param string $lang Language of the content
     */
    static function sendCacheResponseHeaders
    ($eTag = null, $last_modified = null, $expires = null, $control = 'private', $max_age = 0,  $lang = 'en-us')
    {
        if(isset($eTag)){
            header('ETag: "'. $eTag .'"');
        }
        if(isset($last_modified) ){
            if(! $last_modified instanceof DateTime){
                CacheService::httpResponseHeader(500);
                die;
            }
            header('Last-Modified: '. $last_modified->format(DATE_RFC2822));
        }
        if(isset($expires)){
            header("Expires: " . $expires->format(DATE_RFC2822));
        }
        header("Cache-Control: " . $control); 
        header("Cache-Control: max-age=" . (string)$max_age);
        header("Content-language: $lang");
    }
    
    
    /**
     * Wrapper for http_response_code. Sends the header associated with the code.
     * http://php.net/manual/en/function.http-response-code.php for list of codes.
     * @param int $code
     */
    static function httpResponseHeader($code)
    {
        http_response_code($code);
    }
    
    /**
     * Returns both request and response headers.
     * @return assoc_array
     */
    static function getAllClientHeaders()
    {
        return [
                    'request'=> self::getRequestHeaders(),
                    'response'=> self::getResponseHeaders()
                ];
    }
    
    /**
     * Wrapper function for apache_request_headers
     * @return assoc_array
     */
    static function getRequestHeaders()
    {
        return apache_request_headers();
    }
    
    /**
     * Wrapper function for apache_response_headers
     * @return assoc_array
     */
    static function getResponseHeaders()
    {
        return apache_response_headers();
    }
    

    /**
     * Uploads element to Memcache under key $key and with max-age $time
     * @param mixed $element
     * @param string $key 
     * @param timestamp $time Time before the element becomes expired
     */
    static function setToMemcache($element, $key, $time)
    {
        $cache = new phpFastCache("memcache");
        $cached_element = $cache->get($key);
        if($cached_element !== $element){
            $cache->set("$key", $element, $time);
        }
    }
    
    /**
     * Fetches element identified by $key from Memcache, else returns false
     * @param string $key
     * @return false|mixed
     */
    static function getFromMemcache($key)
    {
        $cache = new phpFastCache("memcache");
        $cached_element = $cache->get($key);
        if(!isset($cached_element)){
            return false;
        }else{
            return $cached_element;
        }
    }
    
    /**
     * Verifies if element identified by key is in memcache and then sends the specified header
     * @param string $key
     * @param int $code
     */
    static function responseIfHasInMemcache($key, $code)
    {
        $cached = CacheService::getFromMemcache($key);
        if($cached !== false){
            CacheService::httpResponseHeader($code);
            die;
        }
    }

    
}
