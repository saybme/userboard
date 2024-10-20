<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbUbforms extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_ubforms', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->nullable();
            $table->string('hash')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_ubforms');
    }
}
