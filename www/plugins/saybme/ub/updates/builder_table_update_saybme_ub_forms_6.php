<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForms6 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forms', function($table)
        {
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
            $table->renameColumn('sort_order', 'parent_id');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forms', function($table)
        {
            $table->dropColumn('nest_left');
            $table->dropColumn('nest_right');
            $table->dropColumn('nest_depth');
            $table->renameColumn('parent_id', 'sort_order');
        });
    }
}
