<?php

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Column;

/**
 * Dcat-admin - admin builder based on Laravel.
 *
 * @author jqh <https://github.com/jqhph>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 *
 * extend custom field:
 * Dcat\Admin\Form::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Column::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Filter::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 */

// 禁止本地上传扩展
$request = request();
if ($request->is('*/auth/extensions*')) {
    abort(403, 'No permission');
}

$denyForms = [
    'Dcat\Admin\Http\Forms\InstallFromLocal',
];
if ($request->is('*/dcat-api/form/upload') && in_array($request->input('_form_'), $denyForms)) {
    abort(403, 'No permission');
}
if ($request->is('*/dcat-api/form') && in_array($request->input('_form_'), $denyForms)) {
    abort(403, 'No permission');
}
if ($request->is('*/dcat-api/form/destroy-file') && in_array($request->input('_form_'), $denyForms)) {
    abort(403, 'No permission');
}

$denyActions = [
    'Dcat_Admin_Http_Actions_Extensions_Disable',
    'Dcat_Admin_Http_Actions_Extensions_Enable',
    'Dcat_Admin_Http_Actions_Extensions_InstallFromLocal',
    'Dcat_Admin_Http_Actions_Extensions_Marketplace',
    'Dcat_Admin_Http_Actions_Extensions_Uninstall',
    'Dcat_Admin_Http_Actions_Extensions_Update',
];
if ($request->is('*/dcat-api/action') && in_array($request->get('_action'), $denyActions)) {
    abort(403, 'No permission');
}
// 禁止本地上传扩展

// 禁止富文本插件上传文件
if ($request->is('*/dcat-api/tinymce/upload') || $request->is('*/dcat-api/editor-md/upload')) {
    abort(403, 'No permission');
}

// Admin::style(
//     <<<'CSS'
// .main-footer {
// display: none;
// }
// CSS
// );

if ($request->method() == 'DELETE' && $request->is('*/auth/operation-logs/*')) {
    abort(403, 'No permission');
}

$app = app();
$app->bind(\Dcat\Admin\OperationLog\Http\Middleware\LogOperation::class, \App\Admin\Middleware\LogOperation::class);

Column::extend('address', App\Admin\Displayers\CopyableAddress::class);
Column::extend('number', App\Admin\Displayers\Number::class);
Column::extend('hash', App\Admin\Displayers\Hash::class);

Grid::resolving(function (Grid $grid) {
    /* table_collapse */
    $grid->tableCollapse(true);
    /* 表格边框显示 */
    $grid->withBorder();

    /* 工具栏 */
    $grid->toolsWithOutline(false);

    // 切换行操作按钮显示方式
    $grid->setActionClass(Grid\Displayers\Actions::class);

    $grid->filter(function (Grid\Filter $filter) {
        // 过滤器 更改为 panel 布局
        $filter->panel();
    });
});

\Dcat\Admin\Models\Menu::where('uri', 'auth/extensions')->delete();
// \Dcat\Admin\Models\Menu::where('uri', 'auth/operation-logs')->delete();

// Admin::booting(function () {
//     \Dcat\Admin\Admin::middleware(\App\Admin\Middleware\CheckFirstLogin::class);
// });

admin_inject_section(Admin::SECTION['NAVBAR_USER_PANEL'], function () {
    return view('admin.partials.navbar-user-panel', ['user' => Admin::user()]);
});
