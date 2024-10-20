<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbServises6 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->string('tmp')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->dropColumn('tmp');
        });
    }
}
