<?php
/**
 * Class Logentry
 */

namespace Jlab\Eloglib;

use Dotenv\Dotenv;

/**
 * Class Logentry
 *
 * An electronic log book log entry.
 *
 * @package Jlab\Eloglib
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
     * @var User
    protected $author;
     *
     * /**
     * The log entry time
     * Needs to be in ISO 8601 date format (ex: 2004-02-12T15:19:21+00:00)
     * @var string
     */
    protected $created;

    /**
     * The log entry body
     * @var string
     */
    protected $body;

    /**
     * Whether the logentry should be sticky at the top of lists
     * @var integer (0/1 representing boolean)
     */
    protected $sticky;

    /**
     * The log entry body type
     * Indicates the formatting of the text in $body.
     * Valid values correspond to subset of text formats defined in Drupal
     * (plain_text, filtered_html, full_html, etc.)
     * @var string
     */
    protected $body_type;

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
     * Instantiates a Logentry
     *
     * For maximum flexibility, the constructor can accepts any of
     * the following arguments:
     *   1) A DOMDocument or DOMElement object in Logentry.xsd format
     *   2) The name of an XML file in Logentry.xsd format
     *   3) A title and the name of a logbook (e.g. ELOG, TLOG, etc.)
     *   4) A title and an array of logbook names
     *   5) A StdClass object with Drupal 7 node fields and elog module fields
     *
     * @see https://logbooks.jlab.org/schema/Logentry.xsd
     * @see https://github.com/JeffersonLab/elog
     */
    public function __construct()
    {
        $args = func_get_args();
        if (count($args) > 2 or count($args) < 1) {
            throw new LogentryException("Invalid arguments");
        }

//        if (count($args) == 1) {
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
//            } elseif (is_a($args[0], 'StdClass')) {
//                $this->constructFromNode($args[0]);
//            }
//        } elseif (count($args) == 2) {
        if (is_a($args[0], 'StdClass')) {
            $this->constructFromNode($args[0], $args[1]);
        } elseif (is_string($args[0])) {
            $this->constructFromScratch($args[0], $args[1]);
        }
//        }

        $this->setConfig(__DIR__, '.env');
    }

    /**
     * Minimal initialization constructor.
     *
     * @param $title
     * @param $logbooks
     */
    protected function constructFromScratch($title, $logbooks)
    {
        $this->setTitle($title);
        $this->setLogbooks($logbooks);
        $this->setCreated(time());
    }


    /**
     * Constructs a Logentry from a Drupal node object.
     * @param type stdClass $node
     * @todo incorporate Comments
     */
    public function constructFromNode($node, $att_encode = 'url')
    {
        //mypr($node);
        //var_dump($node);

//        $this->setTitle($node->title);
//        $this->setSticky($node->sticky);
//        if ($node->body) {
//            $this->setBody($node->body[$node->language][0]['value'], $node->body[$node->language][0]['format']);
//        }
//        $this->setCreated($node->created);
//        $this->setLognumber($node->field_lognumber[$node->language][0]['value']);
//        // Find the author
//        $user = user_load($node->uid);
//        $this->setAuthor($user->name);
//        foreach ($node->field_logbook[$node->language] as $arr) {
//            $term = taxonomy_term_load($arr['tid']);
//            $this->addLogbook($term->name);
//        }
//        if ($node->field_entrymakers) {
//            foreach ($node->field_entrymakers[$node->language] as $arr) {
//                $this->addEntrymaker($arr['value']);
//            }
//        }
//        if ($node->field_references) {
//            foreach ($node->field_references[$node->language] as $arr) {
//                if ($arr['value'] > FIRST_LOGNUMBER) {
//                    //current logbook
//                    $this->addReference('logbook', $arr['value']);
//                } else {
//                    //legacy elog
//                    $this->addReference('elog', $arr['value']);
//                }
//            }
//        }
//        if (isset($node->field_extern_ref)){
//            if ($node->field_extern_ref) {
//                foreach ($node->field_extern_ref[$node->language] as $delta => $arr) {
//                    foreach ($arr as $ref_name => $ref_value) {
//                        $this->addReference($arr['ref_name'], $arr['ref_id']);
//                    }
//                }
//            }
//        }
//        if ($node->field_tags) {
//            foreach ($node->field_tags[$node->language] as $arr) {
//                $term = taxonomy_term_load($arr['tid']);
//                $this->addTag($term->name);
//            }
//        }
//        // Drupal stores images and files separately, but to the
//        // elog API, they're both just "attachments
//        if ($node->field_image) {
//            foreach ($node->field_image[$node->language] as $arr) {
//                if (stristr($arr['uri'], 'public:/') && $att_encode != 'base64') {
//                    //$url = str_replace('public:/',$GLOBALS['base_url']."/files", $arr['uri']);
//                    $url = file_create_url($arr['uri']);
//                    $this->addAttachmentURL($url, $arr['title'], $arr['filemime']);
//                } else {
//                    $file = drupal_realpath($arr['uri']);
//                    $this->addAttachment($file, $arr['title'], $arr['filemime']);
//                }
//            }
//        }
//        if ($node->field_attach) {
//            foreach ($node->field_attach[$node->language] as $arr) {
//                if (stristr($arr['uri'], 'public:/') && $att_encode != 'base64') {
//                    //$url = str_replace('public:/',$GLOBALS['base_url']."/files", $arr['uri']);
//                    $url = file_create_url($arr['uri']);
//                    $this->addAttachmentURL($url, $arr['description'], $arr['filemime']);
//                } else {
//                    $file = drupal_realpath($arr['uri']);
//                    $this->addAttachment($file, $arr['description'], $arr['filemime']);
//                }
//            }
//        }
//        if (isset($node->field_opspr)){
//            if ($node->field_opspr) {
//                foreach ($node->field_opspr[$node->language] as $arr) {
//                    $pr = new stdClass();
//                    foreach ($arr as $k => $v) {
//                        $pr->$k = $v;
//                    }
//                    $this->opspr_events[] = $pr;
//                }
//            }
//        }
//        if (isset($node->field_downtime)){
//            if ($node->field_downtime) {
//                $this->downtime = $node->field_downtime[$node->language];
//            }
//        }
//
//        if (! empty($node->problem_report)){
//            $this->pr = $node->problem_report;
//        }
//        // Fetch the Comments (if any)
//        $cids = comment_get_thread($node, COMMENT_MODE_FLAT, 1000);
//        if (count($cids) > 0) {
//            foreach ($cids as $cid) {
//                $node_comment = comment_load($cid);
//                //var_dump($node_comment);
//                $lang = $node_comment->language;
//                //print "1:".$this->lognumber."\n";
//                //print "2:".$node_comment->comment_body[$lang][0]['value']."\n";
//                $C = new Comment($this->lognumber, $node_comment->comment_body[$lang][0]['value']);
//                $C->setAuthor($node_comment->name);   // Username of comment creator
//                $C->setTitle($node_comment->subject);
//                $C->setCreated($node_comment->created);
//                //@see https://drupal.stackexchange.com/questions/56487/how-do-i-get-the-path-for-public
//                $publicPath = $this->publicPath();
//
//                if (isset($node_comment->field_image) && $node_comment->field_image) {
//                    foreach ($node_comment->field_image[$node_comment->language] as $arr) {
//                        $file = str_replace('public:/', $publicPath, $arr['uri']);
//                        if (file_exists($file)) {
//                            $C->addAttachment($file, $arr['title'], $arr['filemime']);
//                        }
//                    }
//                }
//                if (isset($node_comment->field_attach) && $node_comment->field_attach) {
//                    foreach ($node_comment->field_attach[$node_comment->language] as $arr) {
//                        $file = str_replace('public:/', $publicPath, $arr['uri']);
//                        if (file_exists($file)) {
//                            $C->addAttachment($file, $arr['title'], $arr['filemime']);
//                        }
//                    }
//                }
//                //var_dump($C);
//                $this->comments[] = $C;
//            }
//        }
        //mypr($this);
    }

    /**
     * Sets the title
     * Limited to 255 characters.
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
     * @throws LogentryException
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
     * Adds a logbook association
     * @param string $logbook
     *
     * @see https://logbooks.jlab.org/logbooks
     */
    public function addLogbook($logbook)
    {
        $key = strtoupper($logbook);
        $this->logbooks[$key] = $logbook;
    }

    /**
     * Sets the internal timestamp of the entry to be astring in ISO 8601 date format
     *   ex: 2004-02-12T15:19:21+00:00
     *
     * @param mixed $date date in a format parsable by php strtotime()
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
     * Loads configuration from a .env file.
     * Defaults to the .env file included with the package.
     *
     * @param string $dir
     * @param string $file
     * @param bool $overload whether config file should replace existing settings (def: FALSE)
     * @throws LogRuntimeException
     */
    function setConfig($dir, $file, $overload = false)
    {
        try {
            $this->config = new Dotenv($dir, $file);
            if ($overload) {
                $this->config->overload();
            } else {
                $this->config->load();
            }
            // Throws if any required env variables are missing
            $this->config->required(array('LOG_ENTRY_SCHEMA_URL', 'SUBMIT_URL', 'DEFAULT_UNIX_QUEUE_PATH'));
        } catch (\Exception $e) {
            throw new LogRuntimeException($e->getMessage());
        }
    }

    /**
     * Magic method allows controlled access to class properties.
     *
     * @param string $var
     * @return mixed
     */
    function __get($var)
    {
        if (property_exists($this, $var)) {
            return $this->$var;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $var .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
    }


}