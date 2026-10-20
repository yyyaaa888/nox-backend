<?php

namespace App\Admin\Renderable;

use App\Services\RewardSerivce;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class AreaList extends LazyRenderable
{
    public function render()
    {
        $userId   = $this->__get('user_id');
        $areaData = RewardSerivce::totalAreaReward($userId);
        $data     = [];
        foreach ($areaData['performance'] as $key => $value) {

            $data[] = [
                $key,
                $value,
            ];
        }

        return Table::make(['团队', '金额'], $data)->withBorder();
    }
}
