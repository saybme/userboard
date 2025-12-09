<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbApplications5 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_applications', function($table)
        {
            $table->integer('price')->default(0)->change();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_applications', function($table)
        {
            $table->integer('price')->default(null)->change();
        });
    }
}
