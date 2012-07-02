<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Rackspace
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
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
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Rackspace
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_Service_Rackspace_Files_Checksum_Interface
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
     */
    function getSum();
    
    /**
     * Calculates checksum
     */
    function calculate();
    
    /**
     * Closes checksum, performing any cleanup
     */
    function close();
}