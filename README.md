Zend Framework Extensions
=========================

This library provides extensions to [ZF1][]'s Rackspace classes.

[ZF1]: http://framework.zend.com

Cloud Files Stream Wrapper
--------------------------

Right now the primary goal is to provide a stream wrapper so you can use PHP's 
normal file system functions with Cloud Files. For example:

    // sets up stream wrapper
    $cf = new Kynx_Service_Rackspace_Files("<user>", "<apiUid>");
    $cf->setServiceNet();
    $cf->registerStreamWrapper();
    
    copy('/path/to/big/file', 'rscf://container/path/to/big/file');

While Cloud Files doesn't actually have a directory structure, it does support
"psuedo-directories". You can (mostly) manipulate these just like real 
directories, although there are some gotchas:

    mkdir('rscf://container/movies'); // returns true, but doesn't do anything
    is_dir('rscf://container/movies'); // oops, returns false: no files inside
    copy('mymovie.mov', 'rscf://container/movies/mymovie.mov');
    is_dir('rscf://container/movies'); // now it returns true

It also makes copying files between Rackspace accounts trivial:

    $cf1 = new Kynx_Service_Rackspace_Files("<user1>", "<apiUid1>");
    $cf1->setServiceNet();
    $cf1->registerStreamWrapper('cf1');
    
    $cf2 = new Kynx_Service_Rackspace_Files("<user2>", "<apiUid2>");
    $cf2->setServiceNet(); // so long as they're in the same datacenter
    $cf2->registerStreamWrapper('cf2');
    
    copy('cf1://container/backup.tgz', 'cf2://container/backup.tgz');


From the Command Line
---------------------

If you need to move files to and from cloud files from the command line - say
in a nightly backup - check out [cfpipe][]. It's a simple utility that uses the
stream wrapper to copy from the STDIN and to the STDOUT.

[cfpipe]: https://gist.github.com/3068629


Caveats
-------

I wrote the wrapper to help migrate a large-ish application to the cloud. 
Previously it had stored data shared between the front end web servers on an
NFS mount. It had file system calls dotted around 400,000+ lines of code. By 
using the stream wrapper I could get it running in the cloud with minimal code 
changes. However...

* **Cloud Files are not local storage**. They are much slower. Even a simple stat 
call like `file_exists()` is going to slow your application down. Use the wrapper
to get up and running, then do some profiling to find out where Cloud Files is
bogging you down.

* **Not every file system function will work**. You can't open a Cloud File in 
read/write or append mode: `fopen('rscf://...', 'a+')` will fail. You can't do 
an `fseek()` or an `ftruncate()` on a Cloud File. And some PHP functions like 
`zip_open()` just don't support streams.

* **1024 character limit on file path**. The object name (the part after 
'rscf://foo/') must not exceed 1024 bytes once URL encoded. This is a Cloud 
Files limit. If you try to use a longer name you will get an exception.

* **50G file size limit**. There _is_ a way to split up larger files into 
segments, but that's still on my todo list.


Other Uses
----------

Sometimes it's more efficient to use the class interface rather than the stream
wrapper. Here's an example of sending a file straight to the user's browser. It
could be useful for a file download service, where the user must be 
authenticated first:

    if ($user->canView($container, $path)) {
        $cf = new Kynx_Service_Rackspace_Files(getenv('OS_USERNAME'), 
                    getenv('OS_PASSWORD'), getenv('OS_AUTH_URL'));
        $cf->setServiceNet();
        $meta = $cf->getMetadataObject($container, $path);
        if (!$meta) {
            $code = $cf->getErrorCode();
            header('HTTP/1.1 ' . $code . ' ' . Zend_Http_Response::responseCodeAsText($code));
            header('Connection: close');
            exit;
        }
        header('Content-Type: '   . $meta['content_type']);
        header('Content-Length: ' . $meta['bytes']);
        header('Last-Modified: '  . $meta['last_modified']);
        header('ETag: '           . $meta['hash']);
        $cf->getObjectIntoStream($container, $path, 'php://output');
    }

Rackspace also exposes a [TempURL][] call that could accomplish much the same 
thing. Another one for the todo list ;)

[TempURL]: http://docs.rackspace.com/files/api/v1/cf-devguide/content/TempURL-d1a4450.html


Alternate Checksum Methods
--------------------------

When streaming a file to the cloud, we have to calculate a checksum. Once the 
transfer is complete, Cloud Files responds with it's own checksum. If the two 
match we know the transfer was successful.

By default the class makes a local copy of the file and then calls `md5_file()` to
generate its checksum. For large files this isn't very efficient: your 
application will pause every time a chunk is written to the file, and pause for
even longer while the final checksum is calculated.

If your system has an MD5 executable - such as 'md5sum' or 'openssl md5' - you 
can avoid this by using the 'Proc' checksummer. It's non-blocking and the 
checksum is calculated on-the-fly. Just do:

    $cf = new Kynx_Service_Rackspace_Files("<user>", "<apiUid>");
    $cf->setChecksummer(new Kynx_Service_Rackspace_Files_Checksum_Proc());
    // continue as usual

It defaults to searching for md5sum in your PATH. See the docblocks if you need 
to specify an alternate executable.


Requirements
------------

* Zend Framework 1.12+. As of writing this is at RC1 which has some Rackspace
issues. They've been patched in trunk, so grab that if a later RC is not 
available.
* PHP 5.2+. I've developed this on PHP 5.3.3 under CentOS6.2.


Links
-----

[Rackspace Cloud Files Developer Guide](http://docs.rackspace.com/files/api/v1/cf-devguide/content/index.html)  
[Rackspace PHP Cloud Files classes](https://github.com/rackspace/php-cloudfiles)  
[PHP's Streams documentation](http://www.php.net/manual/en/book.stream.php)  

