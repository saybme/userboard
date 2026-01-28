<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbNavigations extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_navigations', function($table)
        {
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_navigations', function($table)
        {
            $table->dropColumn('deleted_at');
        });
    }
}
