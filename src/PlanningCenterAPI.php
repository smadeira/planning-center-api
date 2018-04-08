<?php namespace PlanningCenterAPI;

use GuzzleHttp\Client;

trait PlanningCenterAPI
{

    // HTTP Authentication - Personal Access Tokens
    private $pcoApplicationId = null;
    private $pcoSecret = null;

    // Endpoints for PCO Modules
    private $peopleEndpoint = 'https://api.planningcenteronline.com/people/v2/';
    private $servicesEndpoint = 'https://api.planningcenteronline.com/services/v2/';


    private $headers = null;


    public function __construct()
    {
        // Read configuration
        $this->initialize();
    }

    /**
     * Initialize the class.  Called from the constructor
     *
     */
    private function initialize()
    {
        $this->pcoApplicationId = getenv('PCO_APPLICATION_ID', null);
        $this->pcoSecret = getenv('PCO_SECRET', null);
    }


    public function get()
    {
        $client = new Client(); //GuzzleHttp\Client

        $endpoint = $this->peopleEndpoint . 'people';

        $this->headers = ['Accept: application/json', 'Content-type: application/json'];

        try {
            $response = $client->request('GET', $endpoint, [
                'headers' => $this->headers,
                'curl' => $this->setGetCurlopts(),
            ]);

        } catch (GuzzleException $e) {
            print_r($e->getResponse()->getBody()->getContents());
            return false;

        } catch (GuzzleHttp\Exception\ClientException $e) {
            echo $e->getResponse()->getBody()->getContents();
            return false;
        }

        return json_decode($response->getBody(), true);

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
}