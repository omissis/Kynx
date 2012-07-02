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
 * @package    Zend_Http
 * @subpackage Client_Adapter
 * @version    $Id: Stream.php 24593 2012-01-05 20:35:02Z matthew $
 * @copyright  Copyright (c) 2012 Matt Kynaston <matt@kynx.org>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Http_Client_Adapter_Socket
 */
require_once 'Zend/Http/Client/Adapter/Socket.php';

/**
 * Adds support for "Transfer-Encoding: chunked" requests to Zend_Http_Client_Adapter_Socket
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html
 *
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Client_Adapter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Http_Client_Adapter_SocketChunked extends Zend_Http_Client_Adapter_Socket
{
    /**
     * Are we sending chunked request?
     * 
     * @var boolean
     */
    protected $sending_chunked = false;

    /**
     * Starts a chunked transer request
     *
     * @param string        $method
     * @param Zend_Uri_Http $url
     * @param string        $http_ver
     * @param array         $headers
     * @return string Request as text
     */
    public function startChunkedSend($method, $uri, $http_ver = '1.1', $headers = array()) 
    {
        $hasTransferEncoding = false;
        foreach ($headers as $i => $header) {
            if (strstr($header, 'Content-Length:')) unset($headers[$i]);
            if (strstr($header, 'Transfer-Encoding: chunked')) $hasTransferEncoding = true;
        }
        if (!$hasTransferEncoding) $headers[] = 'Transfer-Encoding: chunked';
        
        $this->sending_chunked = true;
        return $this->write($method, $uri, $http_ver, $headers);
    }
     
    /**
     * Appends a chunk to the request
     * 
     * @param string $data
     * @param boolean $last  This is last chunk to send
     * @return boolean       False if no data written
     */   
    public function appendChunk($data, $last = false) 
    {
        if (!is_resource($this->socket)) {
            /**
             * @see Zend_Http_Client_Adapter_Exception
             */
            require_once 'Zend/Http/Client/Adapter/Exception.php';
            throw new Zend_Http_Client_Adapter_Exception("Trying to write but we're not connected");
        }
        if (!$this->sending_chunked) {
            /**
             * @see Zend_Http_Client_Adapter_Exception
             */
            require_once 'Zend/Http/Client/Adapter/Exception.php';
            throw new Zend_Http_Client_Adapter_Exception("Chunked send has not been started");
        }
        if ($data) {
            $data = dechex(strlen($data)) . "\r\n" 
                . $data . "\r\n";
        }
        if ($last) {
            $data = "0\r\n\r\n";;
        }
        if ($data) {
            if (!@fwrite($this->socket, $data)) {
                /**
                * @see Zend_Http_Client_Adapter_Exception
                */
                require_once 'Zend/Http/Client/Adapter/Exception.php';
                throw new Zend_Http_Client_Adapter_Exception('Error writing chunk to server');
            }
            return true;
        }
        return false;
    }
    
    /**
     * Ends chunked transfer request
     */
    public function endChunkedSend() 
    {
        $this->appendChunk('', true);
        $this->sending_chunked = false;
    }
    
    /**
     * Returns true if chunked request is in progress
     * 
     * @return boolean
     */
    public function isSendingChunked()
    {
        return $this->sending_chunked;
    }
}