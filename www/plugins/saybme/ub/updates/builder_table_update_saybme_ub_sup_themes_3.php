<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbSupThemes3 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_sup_themes', function($table)
        {
            $table->string('url')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_sup_themes', function($table)
        {
            $table->dropColumn('url');
        });
    }
}
