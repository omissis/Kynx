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
 * @see Zend_Service_Rackspace_Files_Checksum_Interface
 */
require_once 'Zend/Service/Rackspace/Files/Checksum/Interface.php';

/**
 * Performs checksumming by writing data to temp file
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Rackspace
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Rackspace_Files_Checksum_Tempfile implements Zend_Service_Rackspace_Files_Checksum_Interface
{
    protected $tempFile;
    protected $sum = '';
    
    /**
     * Initializes checksum
     * 
     * @throws Zend_Service_Rackspace_Files_Exception
     */
    public function open()
    {
        $this->tempFile = tmpfile();
        if (!is_resource($this->tempFile)) {
            /**
             * @see Zend_Service_Rackspace_Files_Exception
             */
            require_once 'Zend/Service/Rackspace/Files/Exception.php';
            throw new Zend_Service_Rackspace_Files_Exception("Couldn't open temp file");
        }
    }
    
    /**
     * Appends data to be checksummed
     * 
     * @param string $data
     * @return integer     Length of data appended
     */    
    public function append($data)
    {
        return fwrite($this->tempFile, $data, strlen($data));
    }
    
    /**
     * Returns checksum
     */    
    public function getSum()
    {
        $this->calculate();
        return $this->sum;
    }
    
    /**
     * Calculates checksum
     */
    public function calculate() 
    {
        $sum = false;
        if ($this->tempFile) {
            $meta = stream_get_meta_data($this->tempFile);
            $sum = md5_file($meta['uri']);
            if ($sum) {
                $this->sum .= md5_file($meta['uri']);
            }
            $this->close();
        }
        return $sum;
    }
    
    /**
     * Closes checksum, performing any cleanup
     */
    public function close() 
    {
        if ($this->tempFile) {
            // deletes file
            @fclose($this->tempFile);
            $this->tempFile = false;
        }
    }
}