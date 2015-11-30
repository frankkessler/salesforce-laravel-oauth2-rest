<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesforceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('salesforce_tokens')) {
            Schema::create('salesforce_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('access_token');
                $table->string('refresh_token');
                $table->string('instance_base_url');
                $table->bigInteger('user_id');
                $table->datetime('expires')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
