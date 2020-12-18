<?php
/**
 * Class Logentry
 */

namespace Jlab\Eloglib;

use Dotenv\Dotenv;
use \XMLWriter;

/**
 * Class Logentry
 *
 * An electronic log book log entry.
 *
 * @package Jlab\Eloglib
 *
 *
 * Significant changes from Logentry class in elog Drupal module:
 *   - $body_type renamed to $bodyType
 *
 */
class Logentry
{

    /**
     * @var Dotenv
     */
    protected $config;


    /**
     * The log number
     *
     * This number is null for a new entry and non-null for an entry retrieved
     * from the logbook server.
     *
     * @var integer
     */
    protected $lognumber;

    /**
     * The log entry title.
     * 255 character limit imposed by Drupal database.
     * @var string
     */
    protected $title;

    /**
     * The author of the logentry
     * @var \stdClass
     */
    protected $author;

    /**
     * The log entry time
     * Needs to be in ISO 8601 date format (ex: 2004-02-12T15:19:21+00:00)
     * @var string
     */
    protected $created;

    /**
     * Whether the logentry should be sticky at the top of lists
     * @var integer (0/1 representing boolean)
     */
    protected $sticky;

    /**
     * The log entry body
     * @var string
     */
    protected $body;


    /**
     * Indicates the formatting of the text in $body.
     *
     * Valid values will correspond to text formats defined in Drupal
     *   examples: plain_text, filtered_html, full_html, etc.
     *
     * @var string
     */
    protected $bodyType;

    /**
     * The list of logbooks for the entry
     * @var array
     */
    protected $logbooks = array();

    /**
     * The list of attachments for the entry
     * @var array
     */
    protected $attachments = array();

    /**
     * The list of users credited with making the entry
     * @var array of stdClass {username, firstname, lastname, etc.}
     */
    protected $entrymakers = array();

    /**
     * The list of tags associated with the log entry.
     * Must be valid terms from the tags vocabulary
     * @var array
     */
    protected $tags = array();

    /**
     * The possible list of opspr events
     * @var array
     */
    protected $opspr_events = array();

    /**
     * The object that holds fields of a new-style ProblemReport (PR)
     */
    protected $pr;

    /**
     * The possible downtime fields
     * @var array
     */
    protected $downtime = array();

    /**
     * References to external databases
     * @var array [type][]=>[id]
     */
    protected $references = array();

    /**
     * Notifications to send
     * @var array
     */
    protected $notifications = array();

    /**
     * Comments attached to the Logentry
     * @var array of Comments
     */
    protected $comments = array();

    /**
     * Text to log as reason for a new revision
     */
    protected $revision_reason;


    /**
     * Instantiate a Logentry
     *
     * For maximum flexibility, the constructor can accept any of
     * the following arguments:
     * <ol>
     *   <li> A DOMDocument or DOMElement object in Logentry.xsd format </li>
     *   <li> The name of an XML file in Logentry.xsd format </li>
     *   <li> A title and the name of a logbook (e.g. ELOG, TLOG, etc.) </li>
     *   <li> A title and an array of logbook names </li>
     * </ol>
     * @link https://logbooks.jlab.org/schema/Logentry.xsd
     * @link https://github.com/JeffersonLab/elog
     */
    public function __construct()
    {
        $args = func_get_args();
        if (count($args) > 2 or count($args) < 1) {
            throw new LogentryException("Invalid arguments");
        }

        if (count($args) == 1) {
//            if (is_a($args[0], 'DOMDocument')) {
//                $this->constructFromDom($args[0]);
//            } elseif (is_a($args[0], 'DOMElement')) {
//                //Must convert to a DOMDocument in order to use DOMXpath
//                $dom = new DOMDocument('1.0', 'UTF-8');
//                $dom->appendChild($dom->importNode($args[0], TRUE));
//                //echo $dom->saveXML();
//                $this->constructFromDom($dom);
//            } elseif (is_a($args[0], 'Elog')) {
//                $this->constructFromElog($args[0]);
//            }
        } elseif (count($args) == 2) {
            if (is_string($args[0])) {
                $this->constructFromScratch($args[0], $args[1]);
            }
        }

        $this->setConfig(__DIR__, '.env');
    }

    /**
     * Minimal initialization private constructor.
     *
     * Automatically sets the created and author fields based
     * on the system clock and os username respectively.
     *
     * @param $title
     * @param $logbooks
     */
    protected function constructFromScratch($title, $logbooks)
    {
        $this->setTitle($title);
        $this->setLogbooks($logbooks);
        $this->setCreated(time());
        $this->setDefaultAuthor();
    }

    /**
     * Sets the title.
     *
     * The title is limited to max 255 characters.
     *
     * @param string $title
     * @throws LogentryException
     */
    public function setTitle($title)
    {
        // Would it be kinder to simply truncate?
        if (strlen($title) > 255) {
            throw new LogentryException("Title exceeds limit of 255 characters");
        }
        $this->title = $title;
    }

    /**
     * Sets the logbook(s) to which entry belongs
     *
     * @param mixed $logbooks (logbook name or array of logbook names)
     */
    public function setLogbooks($logbooks)
    {
        $this->logbooks = array();
        is_array($logbooks) ? $bookList = $logbooks : $bookList = array($logbooks);
        foreach ($bookList as $bookName) {
            $this->addLogbook($bookName);
        }
    }

    /**
     * Adds a logbook to the list of logbooks for the entry.
     *
     * @param string $logbook
     *
     * @link https://logbooks.jlab.org/logbooks
     */
    public function addLogbook($logbook)
    {
        $key = strtoupper($logbook);
        $this->logbooks[$key] = $logbook;
    }


    /**
     * Sets the internal timestamp of the entry.
     *
     * Stores it as string in ISO 8601 date format
     *   ex: 2004-02-12T15:19:21+00:00
     *
     * @param mixed $date unix integer timestamp or string parsable by php strtotime()
     */
    public function setCreated($date)
    {
        if (is_numeric($date)) {
            $this->created = date('c', $date);
        } else {
            $this->created = date('c', strtotime($date));
        }
    }

    /**
     * Defaults the author to the user who owns the current CPU process.
     */
    protected function setDefaultAuthor()
    {
        $os_user = posix_getpwuid(posix_getuid());
        $this->setAuthor($os_user['name']);
    }

    /**
     * Sets the author.
     *
     * @param string $username
     * @param array $attributes associative array of additional user attributes
     *
     */
    public function setAuthor($username, array $attributes = array())
    {
        $this->author = new User($username, $attributes);
    }

    /**
     * Loads configuration from a .env file.
     *
     * Defaults to the .env file included with the package.
     * Throws if required environment variables are not set.
     *
     * @param string $dir
     * @param string $file
     * @param bool $overload whether config file should replace existing settings (def: FALSE)
     * @throws LogentryException if required environment variables not present
     */
    function setConfig($dir=".", $file=".env", $overload = false)
    {
        try {
            if ($overload) {
                $this->config = Dotenv::createMutable($dir, $file);
            } else {
                $this->config = Dotenv::createImmutable($dir, $file);
            }
            $this->config->load();
            // Throws if any required env variables are missing
            $this->config->required(array(
                'LOG_ENTRY_SCHEMA_URL',
                'SUBMIT_URL',
                'ELOGCERT_FILE',
                'DEFAULT_UNIX_QUEUE_PATH',
                'DEFAULT_WINDOWS_QUEUE_PATH',
                'EMAIL_DOMAIN'
            ));
        } catch (\Exception $e) {
            throw new LogentryException($e->getMessage());
        }
    }

    /**
     * Adds an entry maker.
     *
     * @param string $username
     * @param array $attributes associative array of additional user attributes
     *
     */
    public function addEntryMaker($username, array $attributes = array())
    {
        $Maker = new User($username, $attributes);
        $this->entrymakers[$Maker->username] = $Maker;
    }

    /**
     * Adds an email adress to notify of the logentry
     *
     * @param $email
     */
    public function addNotify($email)
    {
        if (is_string($email)) {
            if (!stristr($email, '@')) {
                $email .= getenv('EMAIL_DOMAIN');
            }
            $addr = strtolower($email);
            $this->notifications[$addr] = $email;
        }
    }

    /**
     * Adds a reference to external data
     *
     * @param string $type (lognumber, atlis, etc.)
     * @param integer $ref (numeric elog_id, task_id, etc.)
     */
    public function addReference($type, $ref)
    {
        if ($type && $ref) {
            $key = strtolower($type);
            $this->references[$key][$ref] = $ref;
        }
    }

    /**
     * Adds a tag from the tags vocabulary
     *
     * @param string $tag
     * @link https://logbooks.jlab.org/tags
     */
    public function addTag($tag)
    {
        if ($tag) {
            $key = strtolower($tag);
            $this->tags[$key] = $tag;
        }
    }

    /**
     * Sets the body and its content type.
     *
     * @param string $text The content to place in the body
     * @param string $type Specifies the formatting of text: text|html
     */
    public function setBody($text, $type = 'text')
    {
        $this->body = $text;
        $this->bodyType = $type;
    }

    /**
     * sets the lognumber.
     *
     * The lognumber will generally be set only when a logentry is retrieved from the
     * logbook server, not during creation of a new entry.
     *
     * The server will reject submissions of new entries with non-null lognumber except from
     * a small set of privileged users.  Those users would typically specify the lognumber
     * for a new entry only in situations such as importing legacy data.
     *
     * @param integer $num
     */
    public function setLognumber($num)
    {
        if (is_numeric($num)) {
            $this->lognumber = (int)$num;
        }
    }

    /**
     * Adds an attachment from a file.
     *
     * Stores it in the object's attachments array as a base64 encoded string.
     *
     * @param string $filename
     * @param string $caption
     * @param string $type Specify a mime_type (defaults to autodetect)
     * @throws
     */
    public function addAttachment($filename, $caption = '', $type = '')
    {
        $this->attachments[] = new FileAttachment($filename, $caption, $type);
    }


    /**
     * Adds an attachment as a URL reference.
     *
     * @param string $url
     * @param string $caption
     * @param string $type mimeType
     * @throws
     */
    public function addAttachmentURL($url, $caption = '', $type = '')
    {
        $this->attachments[] = new URLAttachment($url, $caption, $type);
    }


    /**
     * Return Logentry object as an XML text string
     *
     * @param string $name A name to use for the encompassing XML tag.
     * @return string
     * @todo Comment, PR, Downtime
     */
    function getXML($name = 'Logentry')
    {

        /* Note that calls to xmlWriter::writeElement seem to implicitly encode
         * html entitites and so we don't want to call htmlspecialchars ourselves
         * because that results in double-encoding and is a problem.
         */
        $xw = new xmlWriter();
        $xw->openMemory();
        $xw->setIndent(true);
        $xw->startElement($name);

        $this->xmlWriteLognumber($xw);
        $this->xmlWriteCreated($xw);
        $this->xmlWriteTitle($xw);
        $this->xmlWriteAuthor($xw);
        $this->xmlWriteLogbooks($xw);
        $this->xmlWriteTags($xw);
        $this->xmlWriteEntrymakers($xw);
        $this->xmlWriteBody($xw);
        $this->xmlWriteNotifications($xw);
        $this->xmlWriteReferences($xw);

        //Placing attachments at the end make it easier on someone
        //who might try and read the xml file in a terminal or editor.
        $this->xmlWriteAttachments($xw);


//

//        if ($n == 'comments' && count($this->comments) > 0) {
//            $xw->startElement('Comments');
//            //$xw->writeRaw("\n");
//            foreach ($var as $comment) {
//                if (method_exists($comment, 'getXML')) {
//                    $xw->writeRaw($comment->getXML());
//                }
//            }
//            $xw->endElement();
//            continue;
//        }
//        if ($n == 'opspr_events' && count($this->opspr_events) > 0) {
//            $xw->startElement('OPSPREvents');
//            foreach ($this->opspr_events as $pr_event) {
//                $xw->startElement('OPSPREvent');
//                foreach (get_object_vars($pr_event) as $mprop => $mval) {
//                    $xw->writeElement($mprop, $mval);
//                }
//                $xw->endElement();
//            }
//            $xw->endElement();
//            continue;
//        }
//        if ($n == 'downtime' && !empty($this->downtime)) {
//            $xw->startElement('Downtime');
//            foreach ($this->downtime as $key => $value) {
//                $xw->writeElement($key, $value);
//            }
//            $xw->endElement();
//            continue;
//        }

//        if ($n == 'pr' && is_a($this->pr, 'ElogPR')) {
//            $xw->writeRaw($this->pr->getXML());
//        }

        $xw->endElement();
        return $xw->outputMemory(true);
    }


    /**
     * Write the lognumber to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteLognumber(\xmlWriter $xw)
    {
        if ($this->lognumber){
            $xw->writeElement('lognumber', $this->lognumber);
        }
    }

    /**
     * Write the title to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteTitle(\xmlWriter $xw)
    {
        $xw->writeElement('title', $this->title);
    }

    /**
     * Write the created timestamp to XML.
     * @param \xmlWriter $xw
     */
    protected function xmlWriteCreated(\xmlWriter $xw)
    {
        $xw->writeElement('created', $this->created);
    }


    /**
     * Write the author timestamp to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteAuthor(\xmlWriter $xw)
    {
        $xw->writeRaw($this->author->getXML('Author'));
    }

    /**
     * Write the logbook names to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteLogbooks(\xmlWriter $xw)
    {
        $xw->startElement('Logbooks');
        foreach ($this->logbooks as $logbook) {
            $xw->writeElement('logbook', $logbook);
        }
        $xw->endElement();
    }

    /**
     * Write the tag names to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteTags(\xmlWriter $xw)
    {
        if (count($this->tags) > 0) {
            $xw->startElement('Tags');
            foreach ($this->tags as $tag) {
                $xw->writeElement('tag', $tag);
            }
            $xw->endElement();
        }
    }

    /**
     * Write the entrymakers to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteEntrymakers(\xmlWriter $xw)
    {
        if (count($this->entrymakers) > 0) {
            $xw->startElement('Entrymakers');
            foreach ($this->entrymakers as $maker) {
                $xw->writeRaw($maker->getXML('Entrymaker'));
            }
            $xw->endElement();
        }
    }

    /**
     * Write the body content and type to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteBody(\xmlWriter $xw)
    {
        if ($this->body != '') {
            $xw->startElement('body');
            switch ($this->bodyType) {
                case 'elog_text' :          //Really was HTML
                case 'text/html' :
                case 'html' :
                case 'trusted_html' :
                case 'full_html' :
                    $body_type = 'html';
                    break;
                default :
                    $body_type = 'text';
            }
            $xw->writeAttribute('type', $body_type);
            $xw->writeCData($this->body);
            $xw->endElement();
        }
    }

    /**
     * Write the email notification recipients to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteNotifications(\xmlWriter $xw)
    {
        if (count($this->notifications) > 0) {
            $xw->startElement('Notifications');
            foreach ($this->notifications as $email) {
                $xw->writeElement('email', $email);
            }
            $xw->endElement();
        }
    }

    /**
     * Write the external data references to XML.
     *
     * @param \xmlWriter $xw
     */
    protected function xmlWriteReferences(\xmlWriter $xw)
    {
        if (count($this->references) > 0) {
            $xw->startElement('References');
            foreach ($this->references as $type => $ref) {
                foreach ($ref as $r) {
                    $xw->startElement('reference');
                    $xw->writeAttribute('type', $type);
                    $xw->text($r);
                    $xw->endElement();
                }
            }
            $xw->endElement();
        }
    }

    /**
     * Write the attachments to XML.
     *
     * @param XMLWriter $xw
     */
    protected function xmlWriteAttachments(\xmlWriter $xw){
        if (count($this->attachments) > 0) {
            $xw->startElement('Attachments');
            foreach ($this->attachments as $attachment) {
                $xw->writeRaw($attachment->getXML('Attachment'));
            }
            $xw->endElement();
        }
    }

    /**
     * Submit the log item directly to the server and return the assigned log number, but
     * fall back to use the queue mechanism as plan B.
     *
     * If the log number returned is zero it indicates the submission was queued instead of being accepted by the server.
     * You can use the whyQueued method to obtain the ServerException encountered if any while attempting
     * to submit directly to the server.
     *
     * @return int The log number, zero means queued
     * @throws LogentryException
     */
    public function submit(){
        // First attempt is to submit the entry and get back immediate
        // confirmation in the form of the log number assigned.
        try {
            return $this->submitNow();
        } catch ( ServerException $e){
            error_log($e->getMessage());
        }

        // Then fall back and attempt queueing instead.
        // Pass false to second parameter to prevent second redundant
        // XML validation attempt.
        $queueFile = LogentryUtil::saveToQueue($this, false);
        if (file_exists($queueFile)){
            return 0;
        }
    }

    /**
     * Submit the log item using the queue mechanism only.
     *
     * @return mixed the filename that was queued or false for failure.
     * @throws IOException if unable to write queue file
     * @throws InvalidXMLException
     */
    public function queue(){
        $queueFile = LogentryUtil::saveToQueue($this);
        if (file_exists($queueFile)){
            return $queueFile;
        }
        return false;
    }

    /**
     * Submit the log item using only the direct submission method with no queue fallback.
     *
     * If an error occurs during submission then an Exception will be thrown
     * instead of falling back to the queue method.
     *
     * @return integer The log number
     * @throws
     */
    public function submitNow(){
        if (! LogentryUtil::isValidEntry($this)){
            throw new InvalidXMLException("Schema validation of the entry fails: \n" . LogentryUtil::validationErrors());
        }
        return LogentryUtil::saveToServer($this);
    }

    /**
     * Return the ServerException which prevented direct submission to the server on the most recent attempt, or null if none.
     *
     * This method allows access to the exception which is masked when the submit method is called and returns
     * with a zero value indicating the submission was queued.
     */
    public function whyQueued(){
        //TODO implement method
    }



    /**
     * Magic method controls access to class properties.
     *
     * @param string $var
     * @return mixed
     */
    function __get($var)
    {
        if (property_exists($this, $var)) {
            $getterFunction = 'get' . ucfirst($var);
            if (method_exists($this, $getterFunction)) {
                return $this->$getterFunction();
            } else {
                return $this->$var;
            }
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $var .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
    }


}