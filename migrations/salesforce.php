<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;

class CreateSalesforceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ($connection = Capsule::connection($this->getConnection())) {
            $connection->useDefaultSchemaGrammar();
        } else {
            $app = app();
            $connection = $app['db']->connection($this->getConnection());
        }

        $schema = new Schema($connection);

        if (!$schema->hasTable('salesforce_tokens')) {
            $schema->create('salesforce_tokens', function (Blueprint $table) {
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
