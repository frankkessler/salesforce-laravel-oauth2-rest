<?php

namespace Frankkessler\Salesforce;

use Frankkessler\Salesforce\Responses\Query\QueryResponse;
use Frankkessler\Salesforce\Responses\Query\SearchResponse;

class Query
{
    /**
     * @var Salesforce
     */
    private $oauth2Client;

    public function __construct($oauth2Client)
    {
        $this->oauth2Client = $oauth2Client;
    }

    /**
     * SOQL Query.
     *
     * @param $query
     *
     * @return QueryResponse
     */
    public function query($query)
    {
        return new QueryResponse(
            $this->oauth2Client->call_api('get', 'query/?q='.urlencode($query))
        );
    }

    /**
     * SOQL Query and follow next URL until all records are gathered.
     *
     * @param $query
     *
     * @return QueryResponse
     */
    public function queryFollowNext($query)
    {
        return new QueryResponse(
            $this->_queryFollowNext('query', $query)
        );
    }

    /**
     * SOQL Query including deleted records.
     *
     * @param $query
     *
     * @return QueryResponse
     */
    public function queryAll($query)
    {
        return new QueryResponse(
            $this->oauth2Client->call_api('get', 'queryAll/?q='.urlencode($query))
        );
    }

    /**
     * SOQL Query including deleted records and follow next URL until all records are gathered.
     *
     * @param $query
     *
     * @return QueryResponse
     */
    public function queryAllFollowNext($query)
    {
        return new QueryResponse(
            $this->_queryFollowNext('queryAll', $query)
        );
    }

    /**
     * Search using the SOSL query language.
     *
     * @param $query
     *
     * @return SearchResponse
     */
    public function search($query)
    {
        //TODO: put response records into records parameter
        return new SearchResponse(
            $this->oauth2Client->call_api('get', 'search/?q='.urlencode($query))
        );
    }

    protected function _queryFollowNext($query_type, $query, $url = null)
    {
        //next url has not been supplied
        if (is_null($url)) {
            $result = $this->oauth2Client->call_api('get', $query_type.'/?q='.urlencode($query));
        } else {
            $result = $this->oauth2Client->rawGetRequest($url);
        }

        if ($result && isset($result['records']) && $result['records']) {
            if (isset($result['nextRecordsUrl']) && $result['nextRecordsUrl']) {
                $new_result = $this->_queryFollowNext($query_type, $query, $result['nextRecordsUrl']);
                if ($new_result && isset($new_result['records'])) {
                    $result['records'] = array_merge($result['records'], $new_result['records']);
                }
            }
        }

        return $result;
    }
}
