<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbFormInputs extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_form_inputs', function($table)
        {
            $table->integer('formgoup_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_form_inputs', function($table)
        {
            $table->dropColumn('formgoup_id');
        });
    }
}
