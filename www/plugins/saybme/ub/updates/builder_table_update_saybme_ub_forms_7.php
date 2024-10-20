<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForms7 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forms', function($table)
        {
            $table->dropColumn('parent_id');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forms', function($table)
        {
            $table->integer('parent_id')->nullable();
        });
    }
}
