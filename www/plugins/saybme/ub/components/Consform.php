<?php namespace Saybme\Ub\Components;

use Saybme\Ub\Models\Consform as ConsformModel;
use Saybme\Ub\Classes\Auth\AuthClass;

class Consform extends \Cms\Classes\ComponentBase
{

    public function componentDetails()
    {
        return [
            'name' => 'Конструктор формы',
            'description' => 'Конструктор формы'
        ];
    }

    public function defineProperties(){
        return [
            'slug' => [
                'title' => 'Slug',
                'description' => 'Slug формы',
                'type' => 'string',
                'default' => '{{ :slug }}',
            ],
            'type' => [
                'title' => 'Тип формы',
                'description' => 'Тип формы',
                'type' => 'dropdown',
                'options' => [
                    'public' => 'Форма доступна для всех',
                    'private' => 'Форма доступна только для зарегистрированных пользователей',
                ],
                'default' => 'public',
            ],
        ];
    }

    public function onRun(){
        $this->consform = $this->getWrapForm();
    }

    private function getWrapForm()
    {
        $slug = $this->property('slug');
        if (is_array($slug)) {
            $slug = trim(implode('/', $slug), '/');
        } else {
            $slug = trim((string) $slug, '/');
        }

        if ($slug === '') {
            return null;
        }

        $page = ConsformModel::where('slug', $slug)->first();
        if (!$page) {
            return null;
        }

        if ($this->property('type') === 'private') {
            $auth = new AuthClass;
            if (!$auth->getActiveUser()) {
                return $this->renderPartial('@guest', [
                    'page' => $page,
                ]);
            }
        }

        return $this->renderPartial('cons/wrap', [
            'page' => $page,
        ]);
    }

    public $consform;

}
