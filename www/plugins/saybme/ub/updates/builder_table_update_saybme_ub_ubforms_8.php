<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbUbforms8 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_ubforms', function($table)
        {
            $table->string('pdf_tpl')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_ubforms', function($table)
        {
            $table->dropColumn('pdf_tpl');
        });
    }
}
