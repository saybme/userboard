<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbUbforms2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_ubforms', function($table)
        {
            $table->text('content')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_ubforms', function($table)
        {
            $table->dropColumn('content');
        });
    }
}
