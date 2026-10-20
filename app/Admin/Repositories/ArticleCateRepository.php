<?php

namespace App\Admin\Repositories;

use App\Language;
use App\Models\ArticleCate as Model;
use App\Models\ArticleCateAttr as AttrModel;
use Dcat\Admin\Form;
use Dcat\Admin\Repositories\EloquentRepository;
use Dcat\Admin\Show;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ArticleCateRepository extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    private const fields = ['name'/* , 'color' */];

    private const attrsFields = ['name'];

    public function edit(Form $form): array
    {
        $id            = $form->getKey();
        $data          = Model::query()->where('id', $id)->first()->toArray();
        $attrs         = AttrModel::getAttrsMap($id);
        $data['attrs'] = $attrs;

        return $data;
    }

    public function detail(Show $show): array
    {
        $id            = $show->getKey();
        $data          = Model::query()->where('id', $id)->first()->toArray();
        $attrs         = AttrModel::getAttrsMap($id);
        $data['attrs'] = $attrs;

        return $data;
    }

    public function store(Form $form)
    {

        $result = null;

        DB::transaction(function () use ($form, &$result) {

            $updates = $form->updates();

            $attrs = $updates['attrs'] ?? [];
            if ($attrs && isset($attrs[Language::ADMIN_DEFAULT])) {
                $updates = array_merge($updates, Arr::only($attrs[Language::ADMIN_DEFAULT], self::fields));
            }

            $model = $this->model();
            $data  = Arr::only($updates, self::fields);
            foreach ($data as $column => $value) {
                $model->setAttribute($column, $value);
            }
            $result = $model->save();

            if ($attrs) {
                foreach (Language::ALL as $lang) {
                    if (! isset($attrs[$lang])) {
                        continue;
                    }
                    AttrModel::store($model->id, $lang, Arr::only($attrs[$lang], self::attrsFields));
                }
            }
        });

        return $result;
    }

    public function update(Form $form)
    {
        $id = $form->getKey();
        if (! $id) {
            return false;
        }

        $result = null;

        DB::transaction(function () use ($id, $form, &$result) {

            $model = $this->model();
            if (! $model->getKey()) {
                $model->exists = true;

                $model->setAttribute($model->getKeyName(), $form->getKey());
            }

            $updates = $form->updates();

            $attrs = $updates['attrs'] ?? [];
            if ($attrs) {
                foreach (Language::ALL as $lang) {
                    if (! isset($attrs[$lang])) {
                        continue;
                    }
                    $attr = $attrs[$lang];
                    if ($lang == Language::ADMIN_DEFAULT) {
                        $updates = array_merge($updates, Arr::only($attr, self::fields));
                    }
                    AttrModel::store($id, $lang, Arr::only($attr, self::attrsFields));
                }
            }

            $data   = Arr::only($updates, self::fields);
            $result = $model->update($data);
        });

        return $result;
    }
}
