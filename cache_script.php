<?php
/**
 * The idea behind this script comes from 
 * http://www.informatics-tech.com/how-to-leverage-browser-caching-without-htaccess.html
 * 
 * This script allows for independent caching of each files of a page via a specific GET url
 * 
 */
ob_start();
require_once 'cache_service.class.php';
$filename = $_GET['file'];
$memkey = $_GET['memkey'];


//Sanitizing the $_GET['memkey']
if(isset($memkey) && strlen($memkey) == 32 && ctype_xdigit($memkey)){
    $params = CacheService::getFromMemcache($memkey);
    if(!isset($params) || !is_array($params)){
        //bad request 400
        CacheService::httpResponseHeader(400);
        die;
    }
}else{
    //bad request 400
    CacheService::httpResponseHeader(400);
    die;
}

$ext = pathinfo($filename, PATHINFO_EXTENSION);
$mime = '';
//Verifies if valid extension
switch ($ext){
    case "css":
        $mime .= "text/css";
        break;
    case "html":
        $mime .= "text/html";
        break;
    case "gif":
        $mime .= "image/gif";
        break;
    case "jpg":
    case "jpeg":
        $mime .= "image/jpg";
        break;
    case "js":
        $mime .= "application/js";
        break;
    case "png":
        $mime .= "image/png";
        break;
    case "xlsx":
        $mime .= "application/excel";
        break;
    case "xml":
        $mime .= "application/xml";
        break;
    default:
        //forbidden 403
        CacheService::httpResponseHeader(403);
        die;
}

if(!file_exists($filename)){
    //not found 404
    CacheService::httpResponseHeader(404);
    die;
}elseif(!is_readable($filename)){
    //unauthorized 401
    CacheService::httpResponseHeader(401);
    die;
}else{
    $eTag = $params['eTag'];
    $last_modified_cache = $params['last_modified'];
    $last_modified = new DateTime('@' . (string)filemtime($filename));
    $expires = $params['expires'];
    $max_age = $params['max_age'];
    $control = $params['control'];
    $lang = $params['lang'];
    CacheService::sendResponseHeaders($eTag, $last_modified, $expires, $control, $max_age, $lang);

    if(isset($last_modified_cache) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        if ($last_modified_cache == $last_modified || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $eTag) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
        if($last_modified_cache != $last_modified){
            $params['last_modified'] = $last_modified;
            CacheService::setToMemcache($params, $memkey, $params['memcache_expire']);
        }
    }
    header("Content-type: $mime");
}


ob_clean();
flush();
readfile($filename);
exit;



?>
