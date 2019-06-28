## Planning Center API Wrapper

A PHP wrapper to access Planning Center data.</p>
## Installation
### Include the Package
This package is installed via Composer and the following assumes you have installed and initialized Composer for 
the project.  Please refer to the <a href="http://getcomposer.org" target="blank">Composer</a> web site for help on getting composer
installed and your initial composer.json created. 

To add the Ministry Platform API to your project, simply require this package:

```shell
composer require smadeira/planning-center-api
```

Or, you can edit your composer.json file directly to add the Ministry Platform API:
```
"require": {
        "php": ">=7.0.0",
        "smadeira/planning-center-api": "^1.0.0"
    },
```

### Update the package
After including the API Wrapper with composer, do a composer update to download the dependencies required for the 
API wrapper to function.

```
composer update
```
The update command will download all the dependencies (including the API wrapper code) to the vendor diretory.  Once this is done, you are ready to 
start development.

Mote: It's a good idea to run "composer update" every so often to download the latest version of the API wrapper and all of its dependencies.  That's the 
beauty of Composer. It manages all of that for you so you don't have to.

## Configuration
There are a few things that need to be done to configure the API wrapper to function in your environment.

### Connection Parameters
This package makes use of vlucas/phpdotenv to manage configuration variables.  In the root of your project, create a .env file with the following contents.  Ensure you
are using the correct URIs, client ID and secret for your installation.

```
# Planning Center API Parameters
PCO_APPLICATION_ID=YOU_PCO_APPLICATION_ID
PCO_SECRET=YOUR_PCO_SECRET
```

### Loading the API Wrapper
At the top of your code you will need to do a couple things to get access to the API Wrapper. You need to include autoload capabilities and load the 
config settings from the .env file

This is an example of what the top of a script might look like.

```php
require_once __DIR__ . '/vendor/autoload.php';

use PlanningCenterAPI\PlanningCenterAPI as PCO;

// Get environment variables
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

```  

## Usage
Usage is straight forward.  Construct and execute your request.

### Execute select query
The API Wrapper uses the same syntax and format as the online Planning Center API page. To execute a simple query you define the various components and execute.
This sample will get all of the People in the People module (currently supports People and Services) with a last name of Smith and includes references to their
addresses, emails and phone numbers.  It is sorted in descending order of last name (Z - A)  

```php
// Get all people named Smith and sort by first name in descending order.  Then, print the results in array format (you would do additional processing 
// of the data depending on your needs.

$pco = new PCO();

$people = $pco->module('people')
            ->table('people')
            ->where('last_name', '=', 'Smith')
            ->includes('addresses,emails,phone_numbers')
            ->order('-first_name')
            ->get();
        
(!$people) ? print_r( $pco->errorMessage() ) : print_r($people);         
```

### The whole script
Here is the whole script...
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use PlanningCenterAPI\PlanningCenterAPI as PCO;

// Get environment variables
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$pco = new PCO();

$people = $pco->module('people')
            ->table('people')
            ->where('last_name', '=', 'Smith')
            ->includes('addresses,emails,phone_numbers')
            ->order('-first_name')
            ->get();
        
(!$people) ? print_r( $pco->errorMessage() ) : print_r($people); 
```

## POST, PUT, PATCH and DELETE are now supported with their corresomnding methods put(), delete(), etc.