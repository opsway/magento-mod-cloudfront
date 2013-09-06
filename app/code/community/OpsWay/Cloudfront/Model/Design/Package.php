<?php
class OpsWay_Cloudfront_Model_Design_Package extends Mage_Core_Model_Design_Package
{

    /**
     * Create a hash for merged file name.
     * Default Magento functionality is to create a md5 stamp of all filenames.
     * In order not to check file modification any more we'll just add file modification time to hash.
     * If hash of all source files mtime is changed - then we should recreate file.
     * Default Magento additionally checks that each source file mtime is greater then targer merged file.
     * There is no sense in it, as it can not be less (actually only if merged file was created by hand that
     * is not very logical)
     *
     * Uses HTTP header "Accept-Encoding" to create different templates for browsers with different gzip-decoding capabilities
     *
     * Uses HTTP schema (HTTP or HTTPS) to create different merged files for secure and non-secure pages.
     *
     * @param array $srcFiles - array of source filenames
     * @param String $extention - extention of target file. For example "js"
     * @return string
     */
    protected function getMergedFilename($srcFiles, $extention) {
        $acceptEncoding = "";
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])
            && strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') !== false) {
            $acceptEncoding = 'gzip';
        }
        foreach ($srcFiles as $file) {
            if (file_exists($file)) {
                $fileNameAndMtimes[] = $file.@filemtime($file);
            }
        }
        return md5(implode(',', $fileNameAndMtimes).$acceptEncoding.Mage::app()->getRequest()->getScheme()) . '.' . $extention;
    }

    /**
     * Merge specified javascript files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedJsUrl($files)
    {
        $targetFilename = $this->getMergedFilename($files,'js');
        $targetDir = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }
        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, null, 'js')) {
            return Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $targetFilename;
        }
        return '';
    }

    /**
     * Merge specified css files and return URL to the merged file on success
     *
     * @param $files
     * @return string
     */
    public function getMergedCssUrl($files)
    {
        // secure or unsecure
        $isSecure = Mage::app()->getRequest()->isSecure();
        $mergerDir = $isSecure ? 'css_secure' : 'css';
        $targetDir = $this->_initMergerDir($mergerDir);
        if (!$targetDir) {
            return '';
        }
        // base hostname & port
        $baseMediaUrl = Mage::getBaseUrl('media', $isSecure);
        // merge into target file
        $targetFilename = $this->getMergedFilename($files,'css');
        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, array($this, 'beforeMergeCss'), 'css')) {
            return $baseMediaUrl . $mergerDir . '/' . $targetFilename;
        }
        return '';
    }

    // Not processing urls with data:image used
    protected function _cssMergerUrlCallback($match)
    {
        $quote = ($match[1][0] == "'" || $match[1][0] == '"') ? $match[1][0] : '';
        $uri = ($quote == '') ? $match[1] : substr($match[1], 1, strlen($match[1]) - 2);
        if (strpos($uri,'data:image') === false) {
           $uri = $this->_prepareUrl($uri);
        }

        return "url({$quote}{$uri}{$quote})";
    }
}