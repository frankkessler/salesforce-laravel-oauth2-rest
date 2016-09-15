<?php


class AuthenticationTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testAuthenticationUrl()
    {
        $url = \Frankkessler\Salesforce\Authentication::returnAuthorizationLink();

        $expected_url_html = '<a href="https://login.salesforce.com/services/oauth2/authorize?response_type=code&access_type=offline&client_id=&redirect_uri=&scope=">Login to Salesforce</a>';
        $this->assertEquals($expected_url_html, $url);
    }
}
