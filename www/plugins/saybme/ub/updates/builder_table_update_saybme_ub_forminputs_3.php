<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForminputs3 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->integer('value_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->dropColumn('value_id');
        });
    }
}
