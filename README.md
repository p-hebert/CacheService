#Cache Utilities
Cache Utilities are a set of utilities I created for optimization purposes during an internship which took place in Fall 2014.
They were specifically made to interact with *khoaofgod/phpfastcache* and *PHP memcache*.
Originally, only CacheService was implemented. 
I completed CacheScript since I thought it was a pretty neat idea for an environment without frameworks such as the one I was working in.
The script is currently testing-ready and should be tested in a developping environment to make sure it is fully working.


##CacheService [cache_service.class.php]
============

A PHP service gathering both server and client-side caching. 
Relies on *khoaofgod/phpfastcache* and http://php.net/manual/en/book.memcache.php for server-side caching.

##CacheScript [cache_script.php]
============

The CacheScript allows the server to manually decide the caching policy for each external file by passing through the script.
The GET parameter is a serialized and encrypted FileBean (or any other key you want). Hence it becomes difficult to link the key to the actual file. 
A FileBean must be already hosted in memcache before the script is loaded.

##FileBean [file_bean.class.php]
============

The FileBean is a bean containing the information to use in the script regarding the file. 
It allows to separate the file from the key used to fetch it, hence encapsulating the details of the file until it is returned to the client.

##index
============

A simple PHP page used to test the script.
