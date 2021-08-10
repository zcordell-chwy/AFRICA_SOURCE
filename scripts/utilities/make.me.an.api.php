<?php

use RightNow\Connect\v1_3 as RNCPHP;

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}
require_once DOCROOT . '/custom/utilities/credential_auth.php';

if (!defined('ALLOW_POST') && !defined('ALLOW_GET') && !defined('ALLOW_PUT') && !defined('ALLOW_PATCH')) {
    die('DEFINES NOT PRESENT: ALLOW_POST, ALLOW_GET, ALLOW_PUT, ALLOW_PATCH');
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if (!defined('ALLOW_POST') || !ALLOW_POST) {
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }
        if (empty($_POST)) {
            $_POST = file_get_contents('php://input');
        }
        break;
    case 'GET':
        if (!defined('ALLOW_GET') || !ALLOW_GET) {
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }
        break;
    case 'PUT':
        if (!defined('ALLOW_PUT') || !ALLOW_PUT) {
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }
        break;
    case 'PATCH':
        if (!defined('ALLOW_PATCH') || !ALLOW_PATCH) {
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }
        if (empty($_PATCH)) {
            $_PATCH = file_get_contents('php://input');
        }

        break;

    default:
        header('HTTP/1.1 405 Method Not Allowed');
        exit();
        break;
}

/**
 * 
 * 
 */
function outputResponse($data = null, $errors = null, $httpCode = 200)
{
    if (!is_null($errors)) {
        $return = (object) array(
            'links' => (object) array(
                'self' =>  'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            ),
            'errors' => $errors
        );
    } else {
        $return = (object) array(
            'links' => (object) array(
                'self' =>  'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            ),
            'data' => $data
        );
    }

    header('HTTP/1.1 ' . $httpCode);
    header('Content-Type: application/vnd.api+json');

    echo json_encode($return);
    return;
}

/**
 * 
 * If $numbRequired is set, the passed number of fields must be present out of $fields
 */
function checkRequired($object, array $fields, $numbRequired = null)
{
    $numberFound = 0;

    foreach ($fields as $key => $value) {
        
        if ((!isset($object->$value)) || (strlen($object->$value) < 1)) {
            if (is_null($numbRequired)) {
                $error = new JsonError('Missing required field: ' . $value, 404, '/data/attributes/');
                throw new JsonEncodedException($error, 400);
            }
        } else {
            $numberFound++;
        }
    }
    if (!is_null($numbRequired) && $numberFound < $numbRequired) {
        $error = new JsonError('At least ' . $numbRequired . ' fields are required out of: ' . implode(', ', $fields), 404, '/data/attributes/');
        throw new JsonEncodedException($error, 400);
    }
}


/***
 * 
 * Make sure we have everything correct for inbound data
 * 
 * @throws exceptions 
 * 
 */
function parseRequestJsonString($rawRequestBody, $requireIncludedPopulated = true)
{
    $includedReturn = array();
    $requestBodyJson = json_decode($rawRequestBody);

    if (is_null($requestBodyJson)) {
        throw new Exception("Unable to parse request body - invalid json");
    }

    if (!isset($requestBodyJson->data)) {
        throw new Exception("Invalid request format - no data array found");
    }
    if (!is_array($requestBodyJson->data)) {
        throw new Exception("Invalid request format - data object not an array");
    }
    if (count($requestBodyJson->data) < 1) {
        throw new Exception("Invalid request format - data array empty");
    }

    foreach ($requestBodyJson->data as $dataIdx => $dataElement) {
        if (!isset($dataElement->type)) {
            throw new Exception("Invalid request format - no data type-element found");
        }
        if (!isset($dataElement->attributes)) {
            throw new Exception("Invalid request format - no data attributes found");
        }
    }

    if ($requireIncludedPopulated) {

        if (count($requestBodyJson->included) < 1) {
            throw new Exception("Invalid request format - included array empty");
        }
        if (!isset($requestBodyJson->included)) {
            throw new Exception("Invalid request format - no included array found");
        }
        if (!is_array($requestBodyJson->included)) {
            throw new Exception("Invalid request format - included object not an array");
        }

        foreach ($requestBodyJson->included as $idx => $includedObj) {
            if (!isset($includedObj->type)) {
                throw new Exception("Invalid request format - included element $idx has no type element");
            }
            if (!isset($includedObj->id)) {
                throw new Exception("Invalid request format - included element $idx has no id element");
            }
            if (!isset($includedObj->attributes)) {
                throw new Exception("Invalid request format - included element $idx has no attributes element");
            }

            if (!isset($includedReturn[$includedObj->type])) {
                $includedReturn[$includedObj->type] = array();
            }
            $includedReturn[$includedObj->type][$includedObj->id] = $includedObj->attributes;
        }
    }
    return array('data' => $requestBodyJson->data, 'included' => $includedReturn);
}

/**
 * Custom Exception to support json/object errors
 */
class JsonEncodedException extends \Exception
{

    /**
     * Json encodes the message and calls the parent constructor.
     *
     * @param null           $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct(json_encode($message), $code, $previous);
    }

    /**
     * Returns the json decoded message.
     *
     * @param bool $assoc
     *
     * @return mixed
     */
    public function getDecodedMessage($assoc = false)
    {
        return json_decode($this->getMessage(), $assoc);
    }
}

/**
 * Error object wrapper to create JSON API compliance error format
 */
class JsonError
{
    public $title;
    public $status;
    public $source;

    public function JsonError($title = 'Generic Error', $status = 0, $pointer = null)
    {
        $this->title = $title;
        $this->status = $status;
        $this->source = (object) array(
            'pointer' => $pointer,
        );
    }
}
