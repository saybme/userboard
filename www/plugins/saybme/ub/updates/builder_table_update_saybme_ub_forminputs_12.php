<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForminputs12 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->text('app_rules')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->dropColumn('app_rules');
        });
    }
}
