<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForminputs extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->string('group_title')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->dropColumn('group_title');
        });
    }
}
