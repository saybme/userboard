<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbProfilePages7 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_profile_pages', function($table)
        {
            $table->renameColumn('type', 'form');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_profile_pages', function($table)
        {
            $table->renameColumn('form', 'type');
        });
    }
}
