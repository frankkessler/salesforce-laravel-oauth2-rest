[![Travis CI Build Status](https://api.travis-ci.org/frankkessler/salesforce-laravel-oauth2-rest.svg)](https://travis-ci.org/frankkessler/salesforce-laravel-oauth2-rest/)
[![Coverage Status](https://coveralls.io/repos/github/frankkessler/salesforce-laravel-oauth2-rest/badge.svg?branch=master)](https://coveralls.io/github/frankkessler/salesforce-laravel-oauth2-rest?branch=master)
[![StyleCI](https://styleci.io/repos/42465034/shield)](https://styleci.io/repos/42465034)
[![Latest Stable Version](https://poser.pugx.org/frankkessler/salesforce-laravel-oauth2-rest/v/stable)](https://packagist.org/packages/frankkessler/salesforce-laravel-oauth2-rest)

# API CHANGES
There are major breaking changes between the 0.3 branch and the 0.4 branch.  Keep in mind that this package IS NOT yet stable.

# INSTALLATION

To install this package, add the following to your composer.json file

```json
frankkessler/salesforce-laravel-oauth2-rest: "0.4.*"
```

## LARAVEL 5 SPECIFIC INSTALLATION TASKS
Add the following to your config/app.php file in the providers array

```php
Frankkessler\Salesforce\Providers\SalesforceLaravelServiceProvider::class,
```

Add the following to your config/app.php file in the aliases array

```php
'Salesforce'    => Frankkessler\Salesforce\Facades\Salesforce::class,
```

Run the following command to pull the project config file and database migration into your project

```bash
php artisan vendor:publish
```

Run the migration

```bash
php artisan migrate
```

##OPTIONAL INSTALLATION

Logging is enabled by default if using Laravel.  If not, add the following to the $config parameter when initializing the Salesforce class.  (This class must implement the Psr\Log\LoggerInterface interface.)

```php
'salesforce.logger' => $class_or_class_name
```

#TOKEN SETUP

Currently, this package only supports the web flow for oauth2.  This utilizes access and refresh tokens in order to grant access.

To get started, you'll have to setup a Connected App in Salesforce.
1. Navigate to Setup -> Create -> Apps
2. In the Connected Apps section, click New
3. Fill out the form including the Api/Oauth section.
  1. Your callback URL must be https and will follow this format: http://yourdomain.com/salesforce/callback
4. Save and wait 10 minutes for the settings to save.

Now that you have your Client Id and Client secret, add them to your .env file:

```php
SALESFORCE_API_DOMAIN=na1.salesforce.com
SALESFORCE_OAUTH_CALLBACK_URL=https://yourdomain.com/salesforce/callback
SALESFORCE_OAUTH_DOMAIN=login.salesforce.com
SALESFORCE_OAUTH_CONSUMER_TOKEN=YOUR_CLIENT_ID_FROM_CREATED_APP
SALESFORCE_OAUTH_CONSUMER_SECRET=YOUR_CLIENT_SECRET_FROM_CREATED_APP
```

To login and authorize this application add the following to your routes.php file.  You may remove these routes once you have your refresh token stored in the database.

```php
Route::get('salesforce/login', '\Frankkessler\Salesforce\Controllers\SalesforceController@login_form');
Route::get('salesforce/callback', '\Frankkessler\Salesforce\Controllers\SalesforceController@process_authorization_callback');
```

Visit https://yourdomain.com/salesforce/login to authorize your application.  Your access token and refresh token will be stored in the salesforce_tokens database table by default.

# EXAMPLES


### Get a sObject record

```php
$objectType = 'Account';
$objectId = 'OBJECT_ID';

$result = Salesforce::sobject()->get($objectType, $objectId);

if($result->success){
    $name = $result->sobject['Name'];
}
```

### Insert a sObject record

```php
$objectType = 'Account';
$objectData = [
    'Name'          => 'Acme',
    'Description'   => 'Account Description',
];

$result = Salesforce::sobject()->insert($objectType, $objectData);

if($result->success){
    $id = $result->id;
}
```

### Update a sObject record

```php
$objectType = 'Account';
$objectId = 'OBJECT_ID';
$objectData = [
    'Name'          => 'Acme',
    'Description'   => 'Account Description',
];

$result = Salesforce::sobject()->update($objectType, $objectId, $objectData);

if($result->success){
    //no data returned
}
```

### Delete a sObject record

```php
$objectType = 'Account';
$objectId = 'OBJECT_ID';

$result = Salesforce::sobject()->delete($objectType, $objectId);

if($result->success){
    //no data returned
}
```

### Perform a SOQL Query

```php
$soql = 'SELECT Id, Name FROM Account LIMIT 1';

$result = Salesforce::query()->query($soql);

if($result->success && $result->totalSize > 0){
    foreach($result->records as $record){
        $account_name = $record['Name'];
    }
}
```

### Perform a SOQL Query that will get all the records

```php
//since all records are grabbed at once and all records will be in memory, test the max size of the data you would like to use with this function before running in production.  Large data sets could throw PHP OUT OF MEMORY errors.

$soql = 'SELECT Id, Name FROM Account LIMIT 1';

$result = Salesforce::queryFollowNext($soql);

if($result->success && $result->totalSize > 0){
    foreach($result->records as $record){
        $account_name = $record['Name'];
    }
}
```

### Perform a SOSL Query

```php
$sosl = 'FIND {Acme} IN ALL FIELDS RETURNING Account(Id, Name ORDER BY LastModifiedDate DESC LIMIT 3)'; 

$result = Salesforce::search($sosl);

if($result->success && $result->totalSize > 0){
    foreach($result->records as $record){
        $account_name = $record['Name'];
    }
}
```

## Bulk Api Processing

```php
$operationType = 'insert';
$objectType = 'Account';
$objectData = [
    [
        'Name'          => 'Acme',
        'Description'   => 'Account Description',
    ],
    [
        'Name'          => 'Acme2',
        'Description'   => 'Account Description2',
    ],
];

$result = Salesforce::bulk()->runBatch($operationType, $objectType, $objectData);

if($result->success){
    $id = $result->id;
}
```