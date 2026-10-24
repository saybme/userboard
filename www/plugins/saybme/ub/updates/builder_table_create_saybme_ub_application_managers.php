<?php namespace Saybme\Ub\Updates;

use Db;
use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbApplicationManagers extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_application_managers', function ($table) {
            $table->integer('application_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->primary(['application_id', 'user_id']);
            $table->index('user_id');
        });

        $managerIds = Db::table('saybme_ub_users')
            ->where('utype_id', 2)
            ->pluck('id');

        if ($managerIds->isEmpty()) {
            return;
        }

        $applicationIds = Db::table('saybme_ub_applications')->pluck('id');
        $rows = [];

        foreach ($applicationIds as $applicationId) {
            foreach ($managerIds as $managerId) {
                $rows[] = [
                    'application_id' => $applicationId,
                    'user_id' => $managerId,
                ];
            }
        }

        if ($rows) {
            Db::table('saybme_ub_application_managers')->insert($rows);
        }
    }

    public function down()
    {
        Schema::dropIfExists('saybme_ub_application_managers');
    }
}
