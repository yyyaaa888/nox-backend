<?php

namespace App\Admin\Repositories;

use App\Models\Config as Model;
use Dcat\Admin\Form;
use Dcat\Admin\Repositories\EloquentRepository;

class Config extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function edit(Form $form): array
    {
        $data = array_column(Model::get()->toArray(), 'value', 'key');

        return $data;
    }
}
