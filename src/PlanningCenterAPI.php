<?php namespace PlanningCenterAPI;

/**
 * Class PlanningCenterAPI
 * @package PlanningCenterAPI
 *
 * Purpose: Access the planning center REST API
 *
 * URL Format: https://api.planningcenteronline.com/module/v2/table/id/associations/id2/association2?parameters
 *
 */

use GuzzleHttp\Client;

class PlanningCenterAPI
{
    /**
     * Application ID from PCO - Personal Access Token part 1
     * HTTP Basic Auth
     *
     * @var null
     */
    private $pcoApplicationId = null;

    /**
     * Secret from PCO - Personal Access Toekn part 2
     * HTTP Basic Auth
     * @var null
     */
    private $pcoSecret = null;

    /**
     * Authorization string resulting from encoding the App ID and Secret
     * as part of HTTP Basic Auth
     * @var null
     */
    private $authorization = null;

    /**
     * Base URL for people module
     * @var string
     */
    private $peopleEndpoint = 'https://api.planningcenteronline.com/people/v2/';

    /**
     * Base URL for the services module
     * @var string
     */
    private $servicesEndpoint = 'https://api.planningcenteronline.com/services/v2/';

    /**
     * Full URL for the request
     * @var null
     */
    private $endpoint = null;

    /**
     * Requested module - required
     * @var null
     */
    private $module = null;

    /**
     * Table to query within the module - required
     * @var null
     */
    private $table = null;

    /**
     * id (Primary Key) for the request - optional
     * @var null
     */
    private $id = null;

    /**
     * Related table.  For example, .../people/2345/emails would get emails for
     * person with id 2345 - optional
     * @var null
     */
    private $associations = null;

    /**
     * 2nd level id for the association
     * @var null
     */
    private $id2 = null;

    /**
     * 2nd level association
     * @var null
     */
    private $associations2 = null;
    /**
     * Max number of rows to return in a request - optional
     * If not provided, will default to 10,000
     * @var null
     */
    private $maxRows = null;

    /**
     * Array of GET parameters that are appended to the URL - optional
     * Inlcludes offset, per_page, where, order and include
     *
     * @var null
     */
    private $parameters = null;

    /**
     * POST data being sent to Planning Center
     * @var null
     */
    private $data = null;

    /**
     * Guzzle error message
     *
     * @var null
     */
    private $errorMessage = null;

    /**
     * HTTP headers required for the Guzzle request
     * @var null
     */
    private $headers = null;

    /**
     * Initialize the class (authentication params, etc.)
     *
     * PlanningCenterAPI constructor.
     */
    public function __construct()
    {
        // Read configuration file
        $this->initialize();
    }

    /**
     * Set the enpoint to look at the requested module
     * Supported modules include people and services
     *
     * @param $module
     * @return $this
     */
    public function module($module)
    {
        if ($module == 'people') {
            $this->endpoint = $this->peopleEndpoint;
        } else {
            $this->endpoint = $this->servicesEndpoint;
        }

        return $this;
    }

    /**
     * Set the "table" (capability) are we using
     * Examples include people, plans, groups, teams, etc.
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the id for a specific entry in a table
     *
     * @param $id
     * @return $this
     */
    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Choose related data for the table we are looking at
     * @param $associations
     * @return $this
     */
    public function associations($associations)
    {
        $this->associations = $associations;

        return $this;
    }

    /**
     * Set the id for a specific associated entry in a table
     * @param $id
     * @return $this
     */
    public function id2($id)
    {
        $this->id2 = $id;

        return $this;
    }

    /**
     * Set the secondary association
     *
     * @param $associations
     * @return $this
     */
    public function associations2($associations)
    {
        $this->associations2 = $associations;

        return $this;
    }

    /**
     * Set the additional info to be included in the results (addresses, email, etc.)
     *
     * @param $includes
     * @return $this
     */
    public function includes($includes)
    {
        $this->parameters['include'] = $includes;

        return $this;
    }

    /**
     * Set the WHERE clause for the query
     * @param $whereClause
     * @return $this
     */
    public function where($field, $operator, $value)
    {
        switch ($operator) {
            case "=" :
                $op = '=';
                break;
            case ">" :
                $op = '[gt]=';
                break;
            case ">=" :
                $op = '[gte]=';
                break;
            case "<" :
                $op = '[lt]';
                break;
            case "<=" :
                $op = '[lte]=';
                break;
        }

        $this->parameters['where'] = 'where[' . $field . ']' . $op . $value;

        return $this;
    }

    /**
     * Set the number of the next record to be returned in the next query
     * @param $rows
     * @return $this
     */
    public function offset($nextRecord)
    {
        $this->parameters['offset'] = $nextRecord;

        return $this;
    }

    /**
     * Set the number of rows to be returned in a result
     * @param $rows | max of 100 per API definition
     * @return $this
     */
    public function per_page($rows)
    {
        // Limit $rows to 100 max per request
        $rows = $rows > 100 ? 100 : $rows;

        $this->parameters['per_page'] = $rows;

        return $this;
    }

    /**
     * Specify sort order for request
     *
     * @param $order
     * @return $this
     */
    public function order($order)
    {
        $this->parameters['order'] = $order;

        return $this;
    }

    /**
     * Specify the data to be used in the POST or PUT operations
     *
     * @param $data
     * @return $this
     */
    public function data($data)
    {
        $this->data = json_encode($data);

        return $this;
    }

    /**
     * Specify an array of parameters
     * @param $parameters
     * @return $this
     */
    public function parameterArray($parameters)
    {
        foreach ($parameters as $name => $value){
            $this->parameters[$name] = $value;
        }

        return $this;
    }

    /**
     * Execute a get with the configured URL - will return all results
     * @return bool|mixed
     */
    public function get($maxRows = 100000)
    {
        // Set the results per page to 100 if not alread set elsewhere
        $this->initializeQueryWindow();

        // Set max rows to be returned if not null
        if ($maxRows > 0) $this->setMaximumRows($maxRows);

        // Initialize the Guzzle client
        $client = new Client(); //GuzzleHttp\Client
        $this->errorMessage = null;

        $results['data'] = [];
        $results['included'] = [];
        $numRows = 0;

        do {
            $endpoint = $this->buildEndpoint();

            // Execute the request
            if (! $r = $this->execute($endpoint, $client)){
                // If failed, reset the parameters and return - error message should be set
                $this->reset();
                return false;
            }            

            // Get the number of rows returned
            $numRows = count($r['data']);

            // Append the result set to the previous results
            $results['data'] = array_merge($results['data'], $r['data']);
            $results['included'] = array_merge($results['included'], $r['included']);

            // Set offset and per_page for next iteration, if any
            $this->setRequestWindow($numRows);

        } while ($this->parameters['offset'] > 0);

        // Reset all query parameters
        $this->reset();

        return $results;
    }

    /**
     * Get the first record found for the request
     * @return bool|mixed
     */
    public function first()
    {
        return $this->get(1);
    }

    /**
     * Execute a raw query.  It accepts a completely formed URL
     * and executes it.
     *
     * @param $endpoint
     * @return mixed
     */
    public function raw($endpoint)
    {
        // Initialize the Guzzle client
        $client = new Client(); //GuzzleHttp\Client
        $this->errorMessage = null;

        // Execute the request
        $results = $this->execute($endpoint, $client);

        return $results;
    }

    /**
     * Create object in Planning Center
     *
     * @param $data
     * @return bool|mixed
     */
    public function post()
    {
        if (! $response = $this->sendData('POST')) {
            return $this->errorMessage;
        } else {
            return $response;
        }
    }

    /**
     * Update data in PCO
     *
     */
    public function put()
    {
        if (! $response = $this->sendData('PUT')) {
            return $this->errorMessage;
        } else {
            return $response;
        }
    }

    /**
     * Update data in PCO
     *
     */
    public function patch()
    {
        if (! $response = $this->sendData('PATCH')) {
            return $this->errorMessage;
        } else {
            return $response;
        }
    }

    /**
     * Exeucte a POST or PUT request
     *
     */
    private function sendData($verb)
    {
        // Initialize the Guzzle client
        $client = new Client(); //GuzzleHttp\Client
        $this->errorMessage = null;

        $endpoint = $this->buildEndpoint();
       
        $this->headers = ['Accept: application/json',
            'Content-type: application/json',
            $this->authorization
        ];

        try {
            $error = false;
            $response = $client->request($verb, $endpoint, [
                'headers' => $this->headers,
                'curl' => $this->setPutCurlopts(),
                'body' => $this->data,
                'auth' => [
                    $this->pcoApplicationId,
                    $this->pcoSecret
                ]
            ]);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            $this->saveErrorMessage($error);
            $error = true;

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            $this->saveErrorMessage($error);
            $error = true;

        }  catch (\GuzzleHttp\Exception\ServerException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            $this->saveErrorMessage($error);
            $error = true;

        } catch (Exception $e) {
            $error = 'Unknown Exception in Guzzle request';
            $this->saveErrorMessage($error);
            $error = true;
        } finally {
            $this->reset();    
        }
        
        return $error ? false : json_decode($response->getBody(), true);
    }



    /**
     * Takes a fully formed request URL and executes it.
     * @param $endpoint
     * @return array
     */
    public function url($endpoint)
    {
        // Initialize the Guzzle client
        $client = new Client(); //GuzzleHttp\Client
        $this->errorMessage = null;

        $results = [];

        // Execute the request
        $r = $this->execute($endpoint, $client);

        // Append the result set to the previous results
        $results = array_merge($results, $r['data']);

        return $results;
    }

    /**
     * returns the error message from the api call
     * @return null
     */
    public function errorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Reset all endpoint parameters to start a fresh query.
     */
    private function reset()
    {
        $this->endpoint = null;
        $this->module = null;
        $this->table = null;
        $this->id = null;
        $this->action = null;
        $this->associations = null;
        $this->id2 = null;
        $this->associations2 = null;

        $this->parameters = null;
    }

    /**
     * Given the number of rows returned in last request, recalculate the offset
     * and the per_page for the next request.
     *
     * @param $numRows
     */
    private function setRequestWindow($numRows)
    {
        if ($this->fullResult($numRows)) {
            if ($numRows + $this->parameters['offset'] < $this->maxRows) {
                // Still have more to get
                $this->parameters['offset'] += $numRows;
                $remaining = $this->maxRows - $this->parameters['offset'];
                $this->parameters['per_page'] = min($remaining, $this->parameters['per_page']);
            } else {
                // Got all we need
                $this->parameters['offset'] = 0;
            }
        } else {
            // Partial per_page results so we are done
            $this->parameters['offset'] = 0;
        }
    }

    /**
     * Set the maximum rows to be returned.  Also, if max rows is 100 or less, set the
     * per_page parameter to that value to get all rows in one request.
     *
     * @param $maxRows
     */
    private function setMaximumRows($maxRows)
    {
        $this->maxRows = $maxRows;

        if ($maxRows <= 100) $this->parameters['per_page'] = $maxRows;
    }

    /**
     * If the number of rows equals the per_page value, a full request result
     * was returned.
     *
     * @param $numRows
     * @return bool
     */
    private function fullResult($numRows)
    {
        return $numRows == $this->parameters['per_page'];
    }


    /**
     * Execute a request using the provided endpoint
     *
     * @param $endpoint
     * @param $client
     * @return bool|mixed
     */
    private function execute($endpoint, $client)
    {

        $this->headers = ['Accept: application/json',
            'Content-type: application/json',
            $this->authorization
        ];

        try {
            $response = $client->request('GET', $endpoint, [
                'headers' => $this->headers,
                'curl' => $this->setGetCurlopts(),
                'auth' => [
                    $this->pcoApplicationId,
                    $this->pcoSecret
                ]
            ]);    

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            $this->saveErrorMessage($error);            
            return false;

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            $this->saveErrorMessage($error);
            return false;

        }  catch (\GuzzleHttp\Exception\ServerException $e) {
            $error = $e->getResponse()->getBody()->getContents();
            $this->saveErrorMessage($error);
            return false;

        } catch (Exception $e) {
            $error = 'Unknown Exception in Guzzle request';
            $this->saveErrorMessage($error);
        }

        return json_decode($response->getBody(), true);
         
    }


    /**
     * Build the endpoint for the request.  It will have the form of:
     *
     * https://api.planningcenteronline.com/services/v2/table/id/association?offset=25&include=addresses,phone_number
     */
    private function buildEndpoint()
    {
        // Append the table - required
        $endpoint = $this->endpoint . $this->table;

        // Append the id if provided - optional
        $endpoint .= ($this->id) ? '/' . $this->id : '';

        // Append association if provided - optional
        $endpoint .= ($this->associations) ? '/' . $this->associations : '';

        // Append the second id if provided - optional
        $endpoint .= ($this->id2) ? '/' . $this->id2 : '';

        // Append second association if provided - optional
        $endpoint .= ($this->associations2) ? '/' . $this->associations2 : '';

        // Handle URL parameters, if provided
        if ($this->hasParameters()) $endpoint .= $this->appendParameters();

        return $endpoint;
    }

    /**
     * Check URL parameters for any non-null values.  IF they exist then return true
     *
     * @return bool
     */
    private function hasParameters()
    {
        return is_array($this->parameters);
    }

    private function appendParameters()
    {
        // Add the ? to the URL
        $params = '?';

        foreach ($this->parameters as $key => $value) {
            if ($key != 'where') {
                // where is special and is constructed elsewhere
                $params .= $key . '=' . $value . '&';
            } else {
                $params .= $value . '&';
            }
        }

        return rtrim($params, '&');
    }

    /**
     * Extract the error messages from the Exception
     * @param $error
     */
    private function saveErrorMessage($error)
    {
        $e = json_decode($error, true);  

        $this->errorMessage = $e;
    }

    /**
     * Set the cUrl Options for a get request
     *
     * @return array
     */
    private function setGetCurlopts()
    {
        $curlopts = [
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POST => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true
        ];

        return $curlopts;
    }

    /**
     * Set the cUrl Options for a POST/PUT request
     *
     * @return array
     */
    private function setPutCurlopts()
    {

        $curlopts = [
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POST => 1,
            // CURLOPT_POSTFIELDS => $this->data,
            CURLOPT_HEADER => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true
        ];

        return $curlopts;
    }

    /**
     * Initialize the class.  Called from the constructor
     *
     */
    private function initialize()
    {
        // Create the Authorization header
        $this->pcoApplicationId = getenv('PCO_APPLICATION_ID', null);
        $this->pcoSecret = getenv('PCO_SECRET', null);
        $this->authorization = 'Authorization: Basic ' . base64_encode($this->pcoApplicationId . ':' . $this->pcoSecret);
    }

    /**
     * For GET queries - need to set the results per page and the page offset
     * if the user didn't specify specific values in building their query.
     * 100 results per page is the max size.  Offset 0 says start at the very first
     * result.
     */
    private function initializeQueryWindow()
    {
        // Set per_page to 100 if not already set elsewhere
        if (is_null($this->parameters) || ! array_key_exists('per_page', $this->parameters)) $this->parameters['per_page'] = 100;

        $this->parameters['per_page'] = $this->parameters['per_page'] ? $this->parameters['per_page'] : 100;

        // Set the offset to 0 if not already set elsewhere
        if (is_null($this->parameters) || ! array_key_exists('offset', $this->parameters)) $this->parameters['offset'] = 0;

        $this->parameters['offset'] = $this->parameters['offset'] ? $this->parameters['offset'] : 0;
    }
}