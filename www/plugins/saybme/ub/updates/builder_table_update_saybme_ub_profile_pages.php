<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbProfilePages extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_profile_pages', function($table)
        {
            $table->text('props')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_profile_pages', function($table)
        {
            $table->text('props')->nullable(false)->change();
        });
    }
}
