<?php
/**
 * The idea behind this script comes from 
 * http://www.informatics-tech.com/how-to-leverage-browser-caching-without-htaccess.html
 * This script allows for independent caching of each files of a page via a specific GET url
 * 
 */

//To avoid any corruption of file, all outputs are put in a buffer then flushed.
ob_start();
require_once 'cache_service.class.php';
require_once 'file_bean.class.php';
$memkey = $_GET['memkey'];

//Sanitizing the $_GET['memkey']
if(isset($memkey) && strlen($memkey) == 32 && ctype_xdigit($memkey)){
    $file = CacheService::getFromMemcache($memkey);
    if(!isset($file) || !$file instanceof FileBean){
        //bad request 400
        CacheService::httpResponseHeader(400);
        exit();
    }
}else{
    //bad request 400
    CacheService::httpResponseHeader(400);
    exit();
}

//Verifies if valid extension
if(!$file->is_cache_script_enabled()){
    //forbidden 403
    CacheService::httpResponseHeader(403);
    exit();
}

$path = $file->get_path();
$headers = $file->get_header_fields();


//Verifies if file exists & is readable, then proceed to set the headers.
if(!file_exists($path)){
    //not found 404
    CacheService::httpResponseHeader(404);
    exit();
}elseif(!is_readable($path)){
    //unauthorized 401
    CacheService::httpResponseHeader(401);
    exit();
}else{
    $eTag = $headers['eTag'];
    $last_modified_cache = $headers['last_modified'];
    $last_modified = new DateTime('@' . (string)filemtime($path));
    $expires = $headers['expires'];
    $max_age = $headers['max_age'];
    $control = $headers['control'];
    $lang = $headers['lang'];
    CacheService::sendResponseHeaders($eTag, $last_modified, $expires, $control, $max_age, $lang);

    //Verifies if the file has been modified since the upload to memcache
    if(isset($last_modified_cache) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        if ($last_modified_cache == $last_modified || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $eTag) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
        //Updates the $last_modified and upload to memcache
        if($last_modified_cache != $last_modified){
            $file->set_last_modified($last_modified);
            CacheService::setToMemcache($file, $memkey, $file->get_memcache_expires());
        }
    }
    header("Content-type: " . $headers['mime']);
}
ob_clean();
flush();
readfile($path);
exit();
?>
