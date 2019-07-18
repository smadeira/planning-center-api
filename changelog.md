# Change Log

## 2.0.1 (2019-07-17)
- Changed vlucas/phpdotenv to version 3 to be compatible with Laravel 5.8 and other packages that need dotenv 3.

## v1.1.5 (2019-06-28)
- Added delete() method to delete a record
- Added id3 to allow a 3rd numeric parameter on the query string.  This structure is now supported:

	https://api.planningcenteronline.com/module/v2/table/id/associations/id2/association2/id3?parameters

## v1.1.4
- Added method parameterArray() that accepts key-value list of parameters for the API call.