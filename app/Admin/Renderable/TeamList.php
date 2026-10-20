<?php

namespace App\Admin\Renderable;

use App\Models\UsddInvestment;
use App\Models\UserRelation;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class TeamList extends LazyRenderable
{
    public function render()
    {
        $userId        = $this->__get('user_id');
        $allTeamMember = UserRelation::getAllDescendants($userId);

        $data = [];
        $list = [];
        foreach ($allTeamMember as $teamMember) {
            $userInvestment = UsddInvestment::getUserInvestment($teamMember->user_id);
            $userInvestment = $userInvestment->map(function ($item) {
                // 关联用户地址
                $item->address = $item->user->address;
                unset($item->user);
                unset($item->user_id);
                unset($item->daily_output);

                return $item;
            })->values();
            $userInvestment = $userInvestment->toArray();
            if (empty($userInvestment) && $userInvestment === []) {
                continue;
            }

            foreach ($userInvestment as $data) {
                $list[] = $data;
            }
        }

        return Table::make(['金额', '时间', '地址'], $list);
    }
}
