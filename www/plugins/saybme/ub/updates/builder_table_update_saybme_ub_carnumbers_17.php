<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbCarnumbers17 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->integer('sum_start')->nullable()->unsigned(false)->default(null)->comment(null)->change();
            $table->integer('sum_end')->nullable()->unsigned(false)->default(null)->comment(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->text('sum_start')->nullable()->unsigned(false)->default(null)->comment(null)->change();
            $table->text('sum_end')->nullable()->unsigned(false)->default(null)->comment(null)->change();
        });
    }
}
