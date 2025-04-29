<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForminputs5 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->string('input_type')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->dropColumn('input_type');
        });
    }
}
