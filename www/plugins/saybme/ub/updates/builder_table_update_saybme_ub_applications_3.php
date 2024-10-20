<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbApplications3 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_applications', function($table)
        {
            $table->integer('status')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_applications', function($table)
        {
            $table->dropColumn('status');
        });
    }
}
