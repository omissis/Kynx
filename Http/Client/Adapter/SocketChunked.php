<?php
/**
 * @category   Kynx
 * @package    Kynx_Http
 * @subpackage Client_Adapter
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/blob/master/LICENSE New BSD
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
 * @category   Kynx
 * @package    Kynx_Http
 * @subpackage Client_Adapter
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/blob/master/LICENSE New BSD
 */
class Kynx_Http_Client_Adapter_SocketChunked extends Zend_Http_Client_Adapter_Socket
{
    /**
     * Are we sending chunked request?
     * 
     * @var boolean
     */
    protected $sendingChunked = false;

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
        
        $this->sendingChunked = true;
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
             * @see Kynx_Http_Client_Adapter_Exception
             */
            require_once 'Kynx/Http/Client/Adapter/Exception.php';
            throw new Kynx_Http_Client_Adapter_Exception("Trying to write but we're not connected");
        }
        if (!$this->sendingChunked) {
            /**
             * @see Kynx_Http_Client_Adapter_Exception
             */
            require_once 'Kynx/Http/Client/Adapter/Exception.php';
            throw new Kynx_Http_Client_Adapter_Exception("Chunked send has not been started");
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
                * @see Kynx_Http_Client_Adapter_Exception
                */
                require_once 'Kynx/Http/Client/Adapter/Exception.php';
                throw new Kynx_Http_Client_Adapter_Exception('Error writing chunk to server');
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
        $this->sendingChunked = false;
    }
    
    /**
     * Returns true if chunked request is in progress
     * 
     * @return boolean
     */
    public function isSendingChunked()
    {
        return $this->sendingChunked;
    }
}