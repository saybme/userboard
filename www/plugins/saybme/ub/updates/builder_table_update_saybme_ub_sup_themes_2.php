<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbSupThemes2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_sup_themes', function($table)
        {
            $table->renameColumn('name', 'num');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_sup_themes', function($table)
        {
            $table->renameColumn('num', 'name');
        });
    }
}
