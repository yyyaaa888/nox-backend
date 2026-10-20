

<div class="card-header pb-1 with-border" style="padding:.9rem 1rem">

    <div>
        <div class="btn-group" style="margin-right:3px">
            <button class="btn btn-primary btn-sm {{ $id }}-tree-tools" data-action="expand">
                <i class="feather icon-plus-square"></i>&nbsp;<span class="d-none d-sm-inline">{{ trans('admin.expand') }}</span>
            </button>
            <button class="btn btn-primary btn-sm {{ $id }}-tree-tools" data-action="collapse">
                <i class="feather icon-minus-square"></i><span class="d-none d-sm-inline">&nbsp;{{ trans('admin.collapse') }}</span>
            </button>
        </div>

        @if($useSave)
            &nbsp;<div class="btn-group" style="margin-right:3px">
                <button class="btn btn-primary btn-sm {{ $id }}-save" ><i class="feather icon-save"></i><span class="d-none d-sm-inline">&nbsp;{{ trans('admin.save') }}</span></button>
            </div>
        @endif

        @if($useRefresh)
            &nbsp;<div class="btn-group" style="margin-right:3px">
                <button class="btn btn-outline-primary btn-sm" data-action="refresh" ><i class="feather icon-refresh-cw"></i><span class="d-none d-sm-inline">&nbsp;{{ trans('admin.refresh') }}</span></button>
            </div>
        @endif

        @if($tools)
            &nbsp;<div class="btn-group" style="margin-right:3px">
                {!! $tools !!}
            </div>
        @endif
    </div>

    <div>
        {!! $createButton !!}
    </div>

</div>

<style>
    .filter-box {
        border-top: 1px solid #eee;
        margin-top: 10px;
        margin-bottom: -.5rem!important;
        padding: 1.8rem;
    }
</style>
<div class="filter-box card p-0" style="padding-bottom: .5rem!important;margin-top: 10px;margin-bottom: 8px;box-shadow: 0 2px 3px 0 rgba(0, 0, 0, 0.04);">
    <div class="card-body">
        <form action="{!! $url !!}" class="form-horizontal grid-filter-form" pjax-container method="get">
            <div class="row mt-1 mb-0">

                <div class="filter-input col-sm-2 ">
                    <div class="form-group"><div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white text-capitalize"><b>用户名</b></span>
                        </div>
                        <input type="text" class="form-control filter-column-user-name" placeholder="请输入要查询的用户名" name="user[name]" value="{{ request('user.name') }}">
                    </div>
                </div></div>

                <div class="filter-input col-sm-3 ">
                    <div class="form-group"><div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white text-capitalize"><b>地址</b></span>
                        </div>
                        <input type="text" class="form-control filter-column-user-address" placeholder="请输入要查询的地址" name="user[address]" value="{{ request('user.address') }}">
                    </div>
                </div></div>

                <div class="filter-input col-sm-2 ">
                    <div class="form-group"><div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white text-capitalize"><b>邀请码</b></span>
                        </div>
                        <input type="text" class="form-control filter-column-user-invite_code" placeholder="请输入要查询的邀请码" name="user[invite_code]" value="{{ request('user.invite_code') }}">
                    </div>
                </div></div>

                <div class="btn-group ml-1 mb-1" style="height: fit-content;margin-right: 10px">
                    <button class="btn btn-primary btn-sm btn-mini submit">
                        <i class="feather icon-search"></i><span class="d-none d-sm-inline">&nbsp;&nbsp;{{ trans('admin.search') }}</span>
                    </button>
                </div>
                <div class="btn-group btn-group-sm default btn-mini" style="height: fit-content"  >
                    @if(!$disableResetButton)
                        <a  href="{!! $url !!}" class="reset btn btn-white btn-sm ">
                            <i class="feather icon-rotate-ccw"></i><span class="d-none d-sm-inline">&nbsp;&nbsp;{{ trans('admin.reset') }}</span>
                        </a>
                    @endif
                </div>
            </div>

        </form>
    </div>
</div>

<div class="card-body table-responsive">
    <div class="dd" id="{{ $id }}">
        <ol class="dd-list">
            @if(request('user.name') || request('user.invite_code') || request('user.address'))
                @if($items)
                    @foreach($items as $branch)
                        @include($branchView)
                    @endforeach
                @else
                    <span class="help-block" style="margin-bottom:0"><i class="feather icon-alert-circle"></i>&nbsp;{{ trans('admin.no_data') }}</span>
                @endif
            @else
                <span class="help-block" style="margin-bottom:0"><i class="feather icon-alert-circle"></i>&nbsp;请输入要查询的用户信息</span>
            @endif
        </ol>
    </div>
</div>

<script require="@jquery.nestable">
    var id = '{{ $id }}';
    var tree = $('#'+id);

    tree.nestable({!! admin_javascript_json($nestableOptions) !!});

    $('.'+id+'-save').on('click', function () {
        var serialize = tree.nestable('serialize'), _this = $(this);
        _this.buttonLoading();
        $.post({
            url: '{{ $url }}',
            data: {
                '{{ \Dcat\Admin\Tree::SAVE_ORDER_NAME }}': JSON.stringify(serialize)
            },
            success: function (data) {
                _this.buttonLoading(false);

                Dcat.handleJsonResponse(data)
            }
        });
    });

    $('.'+id+'-tree-tools').on('click', function(e){
        var action = $(this).data('action');
        if (action === 'expand') {
            tree.nestable('expandAll');
        }
        if (action === 'collapse') {
            tree.nestable('collapseAll');
        }
    });

    @if(! $expand)
    tree.nestable('collapseAll')
    @endif
</script>
