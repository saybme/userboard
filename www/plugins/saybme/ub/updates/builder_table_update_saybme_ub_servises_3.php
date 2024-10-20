<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbServises3 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->integer('srvcategory_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->dropColumn('srvcategory_id');
        });
    }
}
