<?php
/**
 * The idea behind this script comes from 
 * http://www.informatics-tech.com/how-to-leverage-browser-caching-without-htaccess.html
 * 
 * This script allows for independent caching of each files of a page via a specific GET url
 * 
 * 
 * 
 * 
 * 
 */

require_once 'cache_service.class.php';
$filename = $_GET['file'];
$memkey = $_GET['memkey'];


//Sanitizing the $_GET['memkey']
if(strlen($memkey) == 32 && ctype_xdigit($memkey)){
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

if(! file_exists($filename)){
    //not found 404
    CacheService::httpResponseHeader(404);
    die;
}elseif(! is_readable($filename)){
    //unauthorized 401
    CacheService::httpResponseHeader(401);
    die;
}else{
    header('ETag: "'. $eTag .'"');
    header('Last-Modified: '. $last_modified->format(DATE_RFC2822));
    header("Expires: " . $expires->format(DATE_RFC2822));
    header("Cache-Control: private"); 
    header("Cache-Control: max-age=" . (string)($timestamp - time()));

    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $last_modified || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $eTag) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
    }

    header("Content-type: image/jpeg");
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($filename));
header('Content-Transfer-Encoding: binary');
header("Expires: Sat, 20 Oct 2015 00:00:00 GMT");
header("Cache-Control: max-age=2692000, public"); 
header("Pragma: cache"); 
ob_clean();
flush();
readfile($filename);
exit;



?>
