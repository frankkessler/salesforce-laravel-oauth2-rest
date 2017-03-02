[![Travis CI Build Status](https://api.travis-ci.org/frankkessler/salesforce-laravel-oauth2-rest.svg)](https://travis-ci.org/frankkessler/salesforce-laravel-oauth2-rest/)
[![Coverage Status](https://coveralls.io/repos/github/frankkessler/salesforce-laravel-oauth2-rest/badge.svg?branch=master)](https://coveralls.io/github/frankkessler/salesforce-laravel-oauth2-rest?branch=master)
[![StyleCI](https://styleci.io/repos/42465034/shield)](https://styleci.io/repos/42465034)
[![Latest Stable Version](https://img.shields.io/packagist/v/frankkessler/salesforce-laravel-oauth2-rest.svg)](https://packagist.org/packages/frankkessler/salesforce-laravel-oauth2-rest)

# RUNNING UNIT TESTS

There are currently two ways to run the unit tests.  Keep in mind that node is a dependency for running the unit tests regardless of which way you want to run them.

If you have make installed you can run 
```
make tests
```

If you would prefer to see more details about how the unit tests run, you can start the node server and then run the unit tests from another window.
```
node tests/server.js 8126 true
```
Then in a different window:
```
vendor/bin/phpunit
```

# INSTALLATION

To install this package, add the following to your composer.json file

```json
"frankkessler/salesforce-laravel-oauth2-rest": "0.4.*"
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

Currently, this package only supports the web server flow (Authorization Code) and JWT Web Tokens for oauth2.  


## Web Server Flow Setup (Authorization Code)

This utilizes access and refresh tokens in order to grant access.

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

## JWT Web Token

Setup your public and private key pair.  Salesforce recommends you use an RSA 256 key.  Here is a sample script that will generate a key for you in PHP.

```
$privateKeyPassphrase = null;
$csrString = '';
$privateKeyString = '';
$certString = '';

$config = [
    'private_key_type' => \OPENSSL_KEYTYPE_RSA,
    'digest_alg' => 'sha256',
    'private_key_bits' => 2048,
];

$dn = array(
    "countryName" => "US",
    "stateOrProvinceName" => "New York",
    "localityName" => "New York",
    "organizationName" => "SalesforceLaravel",
    "organizationalUnitName" => "SalesforceLaravel",
    "commonName" => "SalesforceLaravel",
    "emailAddress" => "SalesforceLaravel@example.com"
);

$privateKey = openssl_pkey_new($config);

$csr = openssl_csr_new($dn, $privateKey);

$sscert = openssl_csr_sign($csr, null, $privateKey, 365);

openssl_csr_export($csr, $csrString);
file_put_contents(__DIR__.'/../csr.csr', $csrString);

openssl_x509_export($sscert, $certString);
file_put_contents(__DIR__.'/../public.crt', $certString);

openssl_pkey_export($privateKey, $privateKeyString, $privateKeyPassphrase);
file_put_contents(__DIR__.'/../private.key', $privateKeyString);
```

Setup your app in Salesforce:

1. Navigate to Setup -> Create -> Apps
2. In the Connected Apps section, click New
3. Fill out the form including the Api/Oauth section.
  1. Your callback URL must be https and will follow this format: http://yourdomain.com/salesforce/callback
  2. Check off "Use Digital Signature" and upload the PUBLIC key (public.crt) generated in the previous section.
  3. Select the Oauth scopes you want, but make sure you select the refresh_token, offline_access scope or this flow will fail.
4. Save
5. To get up and running as quick as possible you can follow the next few steps, but they might not work for everyone.
6. Navigate to Setup -> Manage Apps -> Connected Apps and click on the App you just created
7. Click edit and select "Admin approved users are pre-authorized" in the Permitted users field
8. Click Save
9. Scroll down on the same page and select the Manage Profiles button to add the profiles that are pre-authorized to use JWT Web Tokens in your app


Add the following variables to your .env file.

```
SALESFORCE_API_DOMAIN=na1.salesforce.com
SALESFORCE_OAUTH_CALLBACK_URL=https://yourdomain.com/salesforce/callback
SALESFORCE_OAUTH_DOMAIN=login.salesforce.com
SALESFORCE_OAUTH_CONSUMER_TOKEN=YOUR_CLIENT_ID_FROM_CREATED_APP
SALESFORCE_OAUTH_CONSUMER_SECRET=YOUR_CLIENT_SECRET_FROM_CREATED_APP
SALESFORCE_OAUTH_AUTH_TYPE="jwt_web_token"
SALESFORCE_OAUTH_JWT_PRIVATE_KEY="/usr/path/to/my/private.key"
SALESFORCE_OAUTH_JWT_PRIVATE_KEY_PASSPHRASE="testpassword" //optional
SALESFORCE_OAUTH_JWT_RUN_AS_USER_NAME="test.use@mycompanyname.com"  //This is the Salesforce username that will be used to connect
```

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

$result = Salesforce::query()->queryFollowNext($soql);

if($result->success && $result->totalSize > 0){
    foreach($result->records as $record){
        $account_name = $record['Name'];
    }
}
```

### Perform a SOSL Query

```php
$sosl = 'FIND {Acme} IN ALL FIELDS RETURNING Account(Id, Name ORDER BY LastModifiedDate DESC LIMIT 3)'; 

$result = Salesforce::query()->search($sosl);

if($result->success && $result->totalSize > 0){
    foreach($result->records as $record){
        $account_name = $record['Name'];
    }
}
```

## Bulk Api Processing

### Insert

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

if($result->id){
    $id = $result->id;
}
```

### Upsert

```php
$operationType = 'upsert';
$objectType = 'Account';
$objectData = [
    [
        'ExternalId__c' => 'ID1',
        'Name'          => 'Acme',
        'Description'   => 'Account Description',
    ],
    [
        'ExternalId__c' => 'ID2',
        'Name'          => 'Acme2',
        'Description'   => 'Account Description2',
    ],
];

$result = Salesforce::bulk()->runBatch($operationType, $objectType, $objectData, ['externalIdFieldName' => 'ExternalId__c']);

if($result->id){
    foreach ($result->batches as $batch) {
        echo $batch->numberRecordsProcessed;
        echo $batch->numberRecordsFailed;
        foreach ($batch->records as $record) {
            if(!$record['success']){
                echo 'Record Failed: '.json_encode($record);
            }
        }
    }
}

```

### Query

```php
$operationType = 'query';
$objectType = 'Account';
$objectData = 'SELECT Id, Name FROM Account LIMIT 10';

$result = Salesforce::bulk()->runBatch($operationType, $objectType, $objectData);

if ($result->id) {
    $id = $result->id;
    foreach ($result->batches as $batch) {
        foreach ($batch->records as $record) {
            $account_id = $record['Id'];
        }
    }
}
```

### Query with PK Chunking

```php
$operationType = 'query';
$objectType = 'Account';
$objectData = 'SELECT Id, Name FROM Account';

$result = Salesforce::bulk()->runBatch($operationType, $objectType, $objectData, [
    'contentType' => 'CSV',
    'Sforce-Enable-PKChunking' => [
        'chunkSize' => 2500,
    ],
]);

if ($result->id) {
    $id = $result->id;
    foreach ($result->batches as $batch) {
        foreach ($batch->records as $record) {
            $account_id = $record['Id'];
        }
    }
}
```

### Query with Class to Process each Batch

```php
$operationType = 'query';
$objectType = 'Account';
$objectData = 'SELECT Id, Name FROM Account LIMIT 10';

$result = Salesforce::bulk()->runBatch($operationType, $objectType, $objectData,[
    'batchProcessor' => CustomBulkBatchProcessor::class, //Class must implement Frankkessler\Salesforce\Interfaces\BulkBatchProcessorInterface
]);

if ($result->id) {
    $id = $result->id;
}
```

### Custom REST Endpoint (GET)

```php
$uri = 'custom_apex_uri_get';

$result = Salesforce::custom()->get($uri);

if($result->http_status == 200){
    $body = $result->raw_body;
}
```

### Custom REST Endpoint (POST)

```php
$uri = 'custom_apex_uri_post';

$result = Salesforce::custom()->post($uri);

if($result->http_status == 200){
    //success
}
```