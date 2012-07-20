<?php
/**
 * @category   Kynx
 * @package    Kynx_Http
 * @subpackage Client
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/blob/master/LICENSE New BSD
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
 * @category   Kynx
 * @package    Kynx_Http
 * @subpackage Client
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/blob/master/LICENSE New BSD
 */
class Kynx_Http_Client_Chunked extends Zend_Http_Client
{
    protected $chunkedAdapter;
    
    /**
     * Constructor
     * 
     * @param Zend_Uri_Http|string $uri
     * @param array $config Configuration key-value pairs.
     */
    function __construct($uri = null, $config = null) {
        $this->config['adapter'] = 'Kynx_Http_Client_Adapter_SocketChunked';
        parent::__construct($uri, $config);
    }
    
    /**
     * Starts sending a request with Transfer-Encoding: chunked
     * 
     * @param type $method
     * @throws Kynx_Http_Client_Exception 
     */
    public function startChunkedSend($method = null)
    {
        if (!$this->uri instanceof Zend_Uri_Http) {
            /**
             * @see Kynx_Http_Client_Exception 
             */
            require_once 'Kynx/Http/Client/Exception.php';
            throw new Kynx_Http_Client_Exception('No valid URI has been passed to the client');
        }

        if ($method) {
            $this->setMethod($method);
        }
        if (!($this->method == 'PUT' || $this->method == 'POST')) {
            /**
             * @see Zend_Http_Client_Adapter_Exception
             */
            require_once 'Kynx/Http/Client/Exception.php';
            throw new Kynx_Http_Client_Exception("Chunked requests only work with PUT or POST methods");
        }

        // Make sure the adapter is loaded
        if (!$this->chunkedAdapter) {
            $this->setChunkedAdapter($this->config['adapter']);
        }
        if (!($this->chunkedAdapter instanceof Kynx_Http_Client_Adapter_SocketChunked)) {
            /**
             * @see Kynx_Http_Client_Exception 
             */
            require_once 'Kynx/Http/Client/Exception.php';
            throw new Kynx_Http_Client_Exception('Adapter does not support chunked request');
        }
        
        $uri = $this->_prepareUri();
        $body = $this->_prepareBody();
        $headers = $this->_prepareHeaders();

        $this->chunkedAdapter->connect($uri->getHost(), $uri->getPort(),
            ($uri->getScheme() == 'https' ? true : false));
        $this->last_request = $this->chunkedAdapter->startChunkedSend($this->method,
            $uri, $this->config['httpversion'], $headers);
        $this->chunkedAdapter->appendChunk($body);
    }
    
    /**
     * Appends chunk of data to request
     * 
     * @param string $data 
     */
    public function appendChunk($data)
    {
        return $this->chunkedAdapter->appendChunk($data);
    }
    
    /**
     * Ends chunked request
     * 
     * @return Zend_Http_Response
     * @throws Kynx_Http_Client_Exception 
     */
    public function endChunkedSend()
    {
        $this->chunkedAdapter->endChunkedSend();
        $response = $this->chunkedAdapter->read();
        if (!$response) {
            /**
             * @see Kynx_Http_Client_Exception 
             */
            require_once 'Kynx/Http/Client/Exception.php';
            throw new Kynx_Http_Client_Exception('Unable to read response, or response is empty');
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
     * @throws Kynx_Http_Client_Exception 
     */
    public function isSendingChunked() 
    {
        if (!($this->getChunkedAdapter() instanceof Kynx_Http_Client_Adapter_SocketChunked)) {
            /**
             * @see Kynx_Http_Client_Exception 
             */
            require_once 'Kynx/Http/Client/Exception.php';
            throw new Kynx_Http_Client_Exception('Adapter does not support sending chunked');
        }
        return $this->chunkedAdapter->isSendingChunked();
    }

    
    /**
     * Clone the URI and add the additional GET parameters to it
     * 
     * @return Zend_Uri_Http
     */
    protected function _prepareUri()
    {
        $uri = clone $this->uri;
        if (! empty($this->paramsGet)) {
            $query = $uri->getQuery();
                if (! empty($query)) {
                    $query .= '&';
                }
            $query .= http_build_query($this->paramsGet, null, '&');
            if ($this->config['rfc3986_strict']) {
                $query = str_replace('+', '%20', $query);
            }

            // @see ZF-11671 to unmask for some services to foo=val1&foo=val2
            if ($this->getUnmaskStatus()) {
                if ($this->_queryBracketsEscaped) {
                    $query = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $query);
                } else {
                    $query = preg_replace('/\\[(?:[0-9]|[1-9][0-9]+)\\]=/', '=', $query);
                }
            }

            $uri->setQuery($query);
        }
        return $uri;
    }

    /**
     * Load the connection adapter
     *
     * @return Zend_Http_Client_Adapter_Interface $adapter
     */
    public function getChunkedAdapter()
    {
        if (null === $this->chunkedAdapter) {
            $this->setChunkedAdapter($this->config['adapter']);
        }

        return $this->chunkedAdapter;
    }

    /**
     * Load the connection adapter
     *
     * While this method is not called more than one for a client, it is
     * seperated from ->request() to preserve logic and readability
     *
     * @param Zend_Http_Client_Adapter_Interface|string $adapter
     * @return null
     * @throws Zend_Http_Client_Exception
     */
    public function setChunkedAdapter($adapter)
    {
        if (is_string($adapter)) {
            try {
                Zend_Loader::loadClass($adapter);
            } catch (Zend_Exception $e) {
                /** @see Zend_Http_Client_Exception */
                require_once 'Zend/Http/Client/Exception.php';
                throw new Zend_Http_Client_Exception("Unable to load adapter '$adapter': {$e->getMessage()}", 0, $e);
            }

            $adapter = new $adapter;
        }

        if (! $adapter instanceof Zend_Http_Client_Adapter_Interface) {
            /** @see Zend_Http_Client_Exception */
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception('Passed adapter is not a HTTP connection adapter');
        }

        $this->chunkedAdapter = $adapter;
        $config = $this->config;
        unset($config['adapter']);
        $this->chunkedAdapter->setConfig($config);
    }
}