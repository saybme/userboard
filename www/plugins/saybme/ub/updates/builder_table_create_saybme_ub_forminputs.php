<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbForminputs extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_forminputs', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('title')->nullable();
            $table->string('code')->nullable();
            $table->integer('form_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_forminputs');
    }
}
