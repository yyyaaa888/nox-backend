<?php

use App\Admin\Forms\SetVirtualLevelForm;
use App\Jobs\UserUpgradeJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function handleVirtualLevelForm(array $input)
{
    return (new SetVirtualLevelForm)->handle($input);
}

test('设置虚拟等级成功并触发等级重算', function () {
    Queue::fake();
    $user = User::factory()->create(['status' => 1]);

    $response = handleVirtualLevelForm(['user_id' => (string) $user->id, 'level' => '3']);

    $data = $response->toArray();
    expect(User::find($user->id)->virtual_level)->toBe(3)
        ->and($data['status'])->toBeTrue()
        ->and($data['data']['message'])->toBe('设置成功');
    Queue::assertPushed(UserUpgradeJob::class);
});

test('重复设置虚拟等级覆盖旧值', function () {
    Queue::fake();
    $user = User::factory()->create(['status' => 1, 'virtual_level' => 5]);

    handleVirtualLevelForm(['user_id' => (string) $user->id, 'level' => '2']);

    expect(User::find($user->id)->virtual_level)->toBe(2);
});

test('用户ID为空时返回错误', function () {
    Queue::fake();

    $data = handleVirtualLevelForm(['level' => 3])->toArray();

    expect($data['status'])->toBeFalse()
        ->and($data['data']['message'])->toBe('用户ID不能为空');
    Queue::assertNotPushed(UserUpgradeJob::class);
});

test('用户ID非数字时返回错误', function () {
    $data = handleVirtualLevelForm(['user_id' => 'abc', 'level' => 3])->toArray();

    expect($data['status'])->toBeFalse()
        ->and($data['data']['message'])->toBe('用户ID必须是数字');
});

test('等级不存在时返回错误', function () {
    Queue::fake();
    $user = User::factory()->create(['status' => 1]);

    $data = handleVirtualLevelForm(['user_id' => (string) $user->id, 'level' => 6])->toArray();

    expect($data['status'])->toBeFalse()
        ->and($data['data']['message'])->toBe('等级不存在')
        ->and(User::find($user->id)->virtual_level)->toBe(1);
    Queue::assertNotPushed(UserUpgradeJob::class);
});

test('用户不存在时返回错误', function () {
    $data = handleVirtualLevelForm(['user_id' => '999999', 'level' => 3])->toArray();

    expect($data['status'])->toBeFalse()
        ->and($data['data']['message'])->toBe('用户不存在');
});
