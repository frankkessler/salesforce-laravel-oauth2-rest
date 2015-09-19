{!! \Form::open(array('action' => '\Frankkessler\Salesforce\Controllers\SalesforceController@login_form_submit')) !!}
    <div class="input">
        {!! Form::label('username', 'Username') !!}
        {!! \Form::text('username') !!}
    </div>
    <div class="input">
        {!! Form::label('password', 'Password') !!}
        {!! \Form::password('password') !!}
    </div>
    {!! \Form::submit('Submit') !!}
{!! \Form::close() !!}
