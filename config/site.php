<?php

return [
    'version' => '1.0.0',
    'system_address' => [ // 系统默认地址（不能更改） 这些地址无法登录，仅用于默认邀请码
        '0x0000000000000000000000000000000000000001',
        '0x0000000000000000000000000000000000000002',
        '0x0000000000000000000000000000000000000003',
        '0x0000000000000000000000000000000000000004',
        '0x0000000000000000000000000000000000000005',
        '0x0000000000000000000000000000000000000006',
        '0x0000000000000000000000000000000000000007',
        '0x0000000000000000000000000000000000000008',
        '0x0000000000000000000000000000000000000009',
    ],
    'block_scan_rpc'     => env('BLOCK_SCAN_RPC'),
    'block_scan_timeout' => env('BLOCK_SCAN_TIMEOUT', 20),
    'asset' => [
        'chain_id' => env('ASSET_CHAIN_ID', 56), // 链ID
        'USDT' => [
            'pay_address'      => env('ASSET_PAY_ADDRESS'), // USDT收款地址
            'withdraw_address' => env('WITHDRAW_ADDRESS'), // USDT提现出款签名地址
            'withdraw_key'     => env('WITHDRAW_KEY'), // USDT提现出款签名密钥
        ],
        'NOX' => [
            'pay_address'      => env('ASSET_PAY_ADDRESS'), // NOX收款地址
            'withdraw_address' => env('WITHDRAW_ADDRESS'), // NOX提现出款签名地址
            'withdraw_key'     => env('WITHDRAW_KEY'), // NOX提现出款签名密钥
        ],
    ],
    'pay_abi_path' => env('PAY_ABI_PATH'), // 支付合约ABI路径
    'pay_contract' => env('PAY_CONTRACT'), // 支付合约地址
    'withdraw_abi_path' => env('WITHDRAW_ABI_PATH'), // 提现合约ABI路径
    'withdraw_contract' => env('WITHDRAW_CONTRACT'), // 提现合约地址
];
