<?php
/**
 * @category   Kynx
 * @package    Kynx_Service
 * @subpackage Rackspace_Files_Checksum
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/tree/master/LICENSE New BSD
 */
/**
 * The interface for Zend_Service_Rackspace_Files_Checksum classes
 * 
 * When uploading files in multiple sections the returned ETag is a concatenation
 * of the MD5s for each segment. Implementers should store the results of
 * each calculate call and return that in the getSum method.
 * 
 * @see http://docs.rackspace.com/files/api/v1/cf-devguide/content/Create_Update_Object-d1e1965.html
 *
 * @category   Kynx
 * @package    Kynx_Service
 * @subpackage Rackspace_Files_Checksum
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/tree/master/LICENSE New BSD
 */
interface Kynx_Service_Rackspace_Files_Checksum_Interface
{
    /**
     * Initializes checksum
     */
    function open();
    
    /**
     * Appends data to be checksummed
     * 
     * @param string $data
     * @return integer     Length of data appended
     */
    function append($data);
    
    /**
     * Returns concatenated checksums
     * 
     * @return string
     */
    function getSum();
    
    /**
     * Calculates checksum
     * 
     * @return string   Checksum, false on failure
     */
    function calculate();
    
    /**
     * Closes checksum, performing any cleanup
     */
    function close();
}