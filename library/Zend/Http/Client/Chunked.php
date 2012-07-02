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
 * @subpackage Client
 * @version    $Id: Client.php 24593 2012-01-05 20:35:02Z matthew $
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Http_Client
 */
require_once 'Zend/Http/Client.php';

/**
 * Extends Zend_Http_Client to allow for sending requests with Transfer-Encoding: chunked
 * 
 * Chunked requests enable you to send data when the Content-Length is not known.
 * This is useful when sending data from a stream. 
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html
 * 
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Client
 * @throws     Zend_Http_Client_Exception
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Http_Client_Chunked extends Zend_Http_Client
{
    /**
     * Constructor
     * 
     * @param Zend_Uri_Http|string $uri
     * @param array $config Configuration key-value pairs.
     */
    function __construct($uri = null, $config = null) {
        $this->config['adapter'] = 'Zend_Http_Client_Adapter_SocketChunked';
        parent::__construct($uri, $config);
    }
    
    /**
     * Starts sending a request with Transfer-Encoding: chunked
     * 
     * @param type $method
     * @throws Zend_Http_Client_Exception 
     */
    public function startChunkedSend($method = null)
    {
        if (!$this->uri instanceof Zend_Uri_Http) {
            /**
             * @see Zend_Http_Client_Exception 
             */
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception('No valid URI has been passed to the client');
        }

        if ($method) {
            $this->setMethod($method);
        }
        if (!($this->method == 'PUT' || $this->method == 'POST')) {
            /**
             * @see Zend_Http_Client_Adapter_Exception
             */
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception("Chunked requests only work with PUT or POST methods");
        }

        // Make sure the adapter is loaded
        if (!$this->adapter) {
            $this->setAdapter($this->config['adapter']);
        }
        if (!($this->adapter instanceof Zend_Http_Client_Adapter_SocketChunked)) {
            /**
             * @see Zend_Http_Client_Exception 
             */
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception('Adapter does not support chunked request');
        }
        
        $uri = $this->_prepareUri();
        $body = $this->_prepareBody();
        $headers = $this->_prepareHeaders();

        $this->adapter->connect($uri->getHost(), $uri->getPort(),
            ($uri->getScheme() == 'https' ? true : false));
        $this->last_request = $this->adapter->startChunkedSend($this->method,
            $uri, $this->config['httpversion'], $headers);
        $this->adapter->appendChunk($body);
    }
    
    /**
     * Appends chunk of data to request
     * 
     * @param string $data 
     */
    public function appendChunk($data)
    {
        return $this->adapter->appendChunk($data);
    }
    
    /**
     * Ends chunked request
     * 
     * @return Zend_Http_Response
     * @throws Zend_Http_Client_Exception 
     */
    public function endChunkedSend()
    {
        $this->adapter->endChunkedSend();
        $response = $this->adapter->read();
        if (!$response) {
            /**
             * @see Zend_Http_Client_Exception 
             */
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception('Unable to read response, or response is empty');
        }
        
        /**
         * @see Zend_Http_Response
         */
        require_once 'Zend/Http/Response.php';
        $response = Zend_Http_Response::fromString($response);
        if ($this->config['storeresponse']) {
            $this->last_response = $response;
        }
        
        return $response;
    }
    
    /**
     * Returns true if chunked request in progress
     * 
     * @return boolean
     * @throws Zend_Http_Client_Exception 
     */
    public function isSendingChunked() 
    {
        if (!($this->getAdapter() instanceof Zend_Http_Client_Adapter_SocketChunked)) {
            /**
             * @see Zend_Http_Client_Exception 
             */
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception('Adapter does not support sending chunked');
        }
        return $this->adapter->isSendingChunked();
    }
}