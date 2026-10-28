<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbConsform extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_consform', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('data')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_consform');
    }
}
