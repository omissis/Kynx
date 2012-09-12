<?php
/**
 * @category   Kynx
 * @package    Kynx_Service
 * @subpackage Rackspace_Files_Checksum
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/blob/master/LICENSE New BSD
 */

/**
 * Performs checksumming by writing data to temp file
 *
 * @see Kynx_Service_Rackspace_Files_Checksum_Interface
 *
 * @category   Kynx
 * @package    Kynx_Service
 * @subpackage Rackspace_Files_Checksum
 * @copyright  Copyright (c) 2012 Matt Kynaston (http://www.kynx.org)
 * @license    https://github.com/kynx/Kynx/blob/master/LICENSE New BSD
 */
class Kynx_Service_Rackspace_Files_Checksum_Tempfile implements Kynx_Service_Rackspace_Files_Checksum_Interface
{
    protected $tempFile;
    protected $sum = '';

    /**
     * Initializes checksum
     *
     * @throws Kynx_Service_Rackspace_Files_Exception
     */
    public function open()
    {
        $this->sum = '';
        $this->tempFile = tmpfile();
        if (!is_resource($this->tempFile)) {
            /**
             * @see Kynx_Service_Rackspace_Files_Exception
             */
            throw new Kynx_Service_Rackspace_Files_Exception("Couldn't open temp file");
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