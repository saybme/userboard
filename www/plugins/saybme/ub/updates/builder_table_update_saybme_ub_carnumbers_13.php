<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbCarnumbers13 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->integer('type')->nullable()->unsigned(false)->default(null)->comment(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->time('type')->nullable()->unsigned(false)->default(null)->comment(null)->change();
        });
    }
}
