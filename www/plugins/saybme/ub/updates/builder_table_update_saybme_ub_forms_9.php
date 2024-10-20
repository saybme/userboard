<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForms9 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forms', function($table)
        {
            $table->string('menutitle')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forms', function($table)
        {
            $table->dropColumn('menutitle');
        });
    }
}
