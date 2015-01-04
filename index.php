<?php 
require_once 'cache_service.class.php';
require_once 'file_bean.class.php';

$path = $_SERVER['DOCUMENT_ROOT'] . 'cache_util/octocat.png';
$memcache_time = 60;
$file = new FileBean($path, $memcache_time);
$key = md5(serialize($file));
CacheService::setToMemcache($file, $key, $memcache_time);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="author" content="Philippe Hebert"/>
    <title>cache_util</title>
</head>
<body>
    <img src="<?= $_SERVER['DOCUMENT_ROOT'] . "cache_util/cache_script.php?memkey='" . $key . "'"?>" height='512' width='512' alt='octocat.png'>
</body>
</html>