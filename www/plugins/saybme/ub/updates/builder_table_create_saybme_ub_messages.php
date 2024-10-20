<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbMessages extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_messages', function($table)
        {
            $table->increments('id')->unsigned();
            $table->text('comment')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('status')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_messages');
    }
}
