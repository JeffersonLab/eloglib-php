<?php
/**
 * Created by PhpStorm.
 * User: theo
 * Date: 9/7/17
 * Time: 2:43 PM
 */

namespace Jlab\Eloglib;


use DOMDocument;
use DOMXPath;

class LogentryUtil
{

    /**
     * Stores the most recently returned message from saveToServer.
     * @var string
     */
    public static $lastServerMsg;


    /**
     * Saves a Logentry object to an XML file
     *
     * @param string $filename
     * @param Logentry $entry
     * @throws
     */
    public static function saveToFile($filename, Logentry $entry){
        if (file_put_contents($filename, $entry->getXML()) === false){
            throw new Exception("Failed to write $filename");
        }
    }

    /**
     * Returns a file name in the recommended format:
     *    YYYYMMDD_HHMMSS_PID_HOSTNAME_RND.xml
     * @return string
     */
    public static function queueFileName(){
        $pid = posix_getpid();
        $hostname = trim(`hostname`);
        $date = date('Ymd');
        $time = date('His');
        $name = sprintf("%s_%s_%s_%s_%d.xml",$date,$time,$pid,$hostname,rand(1,999));
        return $name;
    }

    protected function hostOS(){

    }

    public static function queuePath(){
        return getenv('DEFAULT_UNIX_QUEUE_PATH');
    }

    /**
     * Saves a Logentry's XML output to a file in the elog queue directory
     * Returns the name of the file that was saved
     *
     * @param Logentry $entry
     * @return string
     * @throws
     */
    public static function saveToQueue(Logentry $entry){
        // Try, and if necessary, keep trying until we get a filename
        // that doesn't already exist in the queue directory.
        $filename = self::queuePath() . DIRECTORY_SEPARATOR . self::queueFileName();
        while (file_exists($filename)){
            $filename = self::$queueDir . DIRECTORY_SEPARATOR . self::queueFileName();
        }

        if (file_put_contents($filename, $entry->getXML()) === false){
            throw new IOException("Failed to save $filename");
        }

    }


    protected static function tmpXMLFile($xml){
        $basename = self::queueFileName();
        $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $basename;

        while (file_exists($filename)){
            $basename = self::queueFileName();
            $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $basename;
        }
        if (file_put_contents($filename, $xml) === false){
            throw new IOException("Unable to write XML temp file: ".$filename);
        }
        return $filename;
    }

    protected static function submitUrl($filename){
        return getenv('SUBMIT_URL') . '/' . urlencode(basename($filename));
    }


    public static function isRelativeFilename($filename){
        return $filename === basename($filename);
    }

    public static function certificateFile(){
        $filename = getenv('ELOGCERT_FILE');
        if (self::isRelativeFilename($filename)){
            $filename = self::userHome() . DIRECTORY_SEPARATOR . $filename;
        }
        return $filename;
    }

    /**
     * Return the user's home directory.
     *
     * The code below was borrowed from https://github.com/drush-ops/drush
     */
    function userHome() {
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        }
        elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? NULL : $home;
    }


    /**
     * Saves a Logentry to the server via HTTPS.
     *
     * Returns boolean indicating sucess or failure.  The raw server response
     * is stored in LogentryUtil::$lastServerMsg
     *
     * @param Logentry $entry
     * @return string
     * @throws
     */
    public static function saveToServer(Logentry $entry){
        $xmlFile = self::tmpXMLFile($entry->getXML());
        var_dump($xmlFile);
        var_dump(self::submitUrl($xmlFile));

        $FH = fopen($xmlFile, 'r');
        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, self::submitUrl($xmlFile));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // cert is self-signed right now
        curl_setopt($ch, CURLOPT_SSLCERT, self::certificateFile());
        curl_setopt($ch, CURLOPT_INFILE, $FH);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($xmlFile));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PUT, true);
        $result = curl_exec($ch);
        fclose($FH);
        var_dump($result);
        if ($result === false){
            throw new IOException('curl failure.  Unable to send file.' .curl_error($ch));
        }else{
            self::$lastServerMsg = $result;
            $success = self::interpretServerResponse($result);
            unlink($xmlFile);
            return $success;
        }

    }
    /**
     * Extracts boolean success or failure from the XML of
     * putServer response.
     *
     * @param $txt
     * @return boolean
     */
    public static function interpretServerResponse($text){
        $dom = new DOMDocument();
        if (! $dom->loadXML($text)){                // Presume garbled response is failure.
            throw new \Exception("Unable to process server response:\n".$text);
            return false;
        }

        $root = $dom->documentElement->tagName;
        if ($root == 'Response'){
            $stat = $dom->documentElement->getAttribute('stat');
            if ($stat == 'ok'){
                $xpath = new DOMXpath($dom);
                $nodes = $xpath->query('lognumber');
                foreach($nodes as $node){
                    $lognumber = $node->nodeValue;
                }
                return $lognumber;
            }
        }else{
            throw new \Exception("Server did not return Response entity in XML:\n".$text);
        }
        return false;
    }
    /**
     * Validates the XML of the logentry against the approriate schema.
     * if it returns false call getValidationErrors() to get the description
     * of what tests failed.
     * @param Logentry $entry
     * @return boolean
     * @throws
     */
    public static function validateEntry(Logentry $entry, $schema){

        // We need to save the current entry to a tempfile and then
        // load it as a DOM document.
        $filename = tempnam(self::$tmpDir,'validateXML_');
        if (file_put_contents($filename, $entry->getXML()) === false){
            throw new Exception("Failed to write temp file $filename");
        }
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->load($filename, LIBXML_PARSEHUGE);
        if ($dom->schemaValidate($schema)){
            return true;
        }

        return false;
    }

    /**
     * Validates the XML file against the approriate schema.
     * if it returns false you should call getValidationErrors() to get
     * the error description
     * of what tests failed.
     * @param string $filename
     * @return boolean
     * @throws
     */
    public static function validateXMLFile($filename, $schema){
        watchdog('elog',$schema);
        if (! is_readable($filename)){
            throw new Exception("Unable to read $filename for validation");
        }
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->load($filename, LIBXML_PARSEHUGE );
        $root = $dom->documentElement->tagName;
        if ($dom->schemaValidate($schema)){
            return true;
        }
        return false;
    }
    /**
     * Returns the buffered error messages from the last XML
     * validation attempt and clears the buffer.
     * @return string;
     */
    static public function getValidationErrors(){
        $return = '';
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $return .= self::getXMLErrorString($error);
        }
        libxml_clear_errors();
        return $return;
    }
    /**
     * Converts a libXML error into human readable form.
     * @param $error a libXML error
     * @return $string
     */
    static public function getXMLErrorString($error)
    {
        $return = '';
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }
        $return .= trim($error->message) .
            "\n  Line: $error->line" .
            "\n  Column: $error->column";
        if ($error->file) {
            $return .= "\n  File: $error->file";
        }
        return "$return\n\n--------------------------------------------\n\n";
    }
    /**
     * returns an XML Response entity.
     *
     * The XML of the wrapped inside <Response> tags like so
     * <Response stat="ok">
     *   <lognumber>123456</lognumber>
     *   <url>https://logboks.jlab.org/entry/123456</url>
     *   <msg />
     * </Response>
     *
     * @param stdClass $O object containing data to return
     * @param string $stat status to return (ok|fail)
     */
    public static function getXMLresponse(stdClass $O, $stat='ok'){
        $xw = new xmlWriter();
        $xw->openMemory();
        $xw->startDocument();
        $xw->setIndent(true);
        $xw->startElement('Response');
        $xw->writeAttribute('stat',$stat);
        $xw->writeRaw("\n");
        //var_dump($O);
        foreach (get_object_vars($O) as $n => $var){
            $xw->writeElement($n, htmlspecialchars($var));
        }
        $xw->endElement();
        $xw->endDocument();
        return $xw->outputMemory(true);
    }

}