<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbServises extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->string('slug')->nullable();
            $table->string('url')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->dropColumn('slug');
            $table->dropColumn('url');
        });
    }
}
