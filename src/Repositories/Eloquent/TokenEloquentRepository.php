<?php

namespace Frankkessler\Salesforce\Repositories\Eloquent;

use Frankkessler\Salesforce\Repositories\TokenRepositoryInterface;
use Frankkessler\Salesforce\Models\SalesforceToken;

class TokenEloquentRepository implements TokenRepositoryInterface{
    public function getRefreshTokenById($user_id){
        return SalesforceToken::findByUserId($user_id)->first();
    }

    public function setRefreshTokenById($user_id, $refresh_token){
        $record = SalesforceToken::findByUserId($user_id)->first();

        if(!$record) {
            $record = new SalesforceToken;
            $record->user_id = $user_id;
        }

        $record->refresh_token = $refresh_token;
        $record->save();
    }
}