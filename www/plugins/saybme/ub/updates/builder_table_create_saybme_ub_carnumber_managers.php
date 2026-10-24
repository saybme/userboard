<?php namespace Saybme\Ub\Updates;

use Db;
use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbCarnumberManagers extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_carnumber_managers', function ($table) {
            $table->integer('carnumber_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->primary(['carnumber_id', 'user_id']);
            $table->index('user_id');
        });

        $managerIds = Db::table('saybme_ub_users')
            ->where('utype_id', 2)
            ->pluck('id');

        if ($managerIds->isEmpty()) {
            return;
        }

        $rows = [];
        foreach (Db::table('saybme_ub_carnumbers')->pluck('id') as $carnumberId) {
            foreach ($managerIds as $managerId) {
                $rows[] = [
                    'carnumber_id' => $carnumberId,
                    'user_id' => $managerId,
                ];
            }
        }

        if ($rows) {
            Db::table('saybme_ub_carnumber_managers')->insert($rows);
        }
    }

    public function down()
    {
        Schema::dropIfExists('saybme_ub_carnumber_managers');
    }
}
