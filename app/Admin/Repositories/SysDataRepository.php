<?php

namespace App\Admin\Repositories;

use App\SysData;
use Dcat\Admin\Form;
use Dcat\Admin\Repositories\Repository;

class SysDataRepository extends Repository
{
    public function edit(Form $form): array
    {
        return SysData::list();
    }
}
