<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbDocuments extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_documents', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('num')->nullable();
            $table->text('description')->nullable();
            $table->text('data')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('hash')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_documents');
    }
}
