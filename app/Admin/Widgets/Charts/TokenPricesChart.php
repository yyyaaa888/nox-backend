<?php

namespace App\Admin\Widgets\Charts;

use App\Models\TokenPrice;
use Dcat\Admin\Grid;
use Dcat\Admin\Widgets\ApexCharts\Chart;
use Illuminate\Support\Carbon;

class TokenPricesChart extends Chart
{
    private Grid $grid;

    public function __construct($containerSelector = null, $options = [])
    {
        parent::__construct($containerSelector, $options);

        $this->setUpOptions();
    }

    /**
     * 初始化图表配置
     */
    protected function setUpOptions()
    {
        $this->options([
            'title' => [
                'text' => 'CHE价格',
            ],
            'chart' => [
                'type'   => 'area',
                'height' => 350,
            ],
            'grid' => [
                'padding' => [
                    'right'  => 40,
                    'left'   => 40,
                    'top'    => 40,
                    'bottom' => 20,
                ],
            ],
            'xaxis' => [
                'type'   => 'datetime',
                'labels' => [
                    'rotate'            => -15,
                    'rotateAlways'      => true,
                    'datetimeFormatter' => [
                        'year'   => 'yyyy',
                        'month'  => 'yyyy-MM',
                        'day'    => 'MM-dd',
                        'hour'   => 'HH:mm',
                        'minute' => 'HH:mm',
                        'second' => 'HH:mm:ss',
                    ],
                ],
            ],
            'yaxis' => [
                'title' => [
                    'text' => '价格 (USDT)',
                ],
            ],
            'tooltip' => [
                'x' => [
                    'format' => 'yyyy-MM-dd HH:mm:ss',
                ],
            ],
        ]);
    }

    /**
     * 处理图表数据
     */
    protected function buildData()
    {
        $query = TokenPrice::query();
        $this->grid->model()->getQueries()->unique()->each(function ($value) use (&$query) {
            if (in_array($value['method'], ['paginate', 'get', 'orderBy', 'orderByDesc'], true)) {
                return;
            }

            $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
        });

        $prices = $query->orderBy('time', 'desc')->select('time', 'price')->limit(100)->get();

        // 按照时间顺序排序 key重设索引
        $prices = $prices->sortBy('time')->values();

        $now       = Carbon::now();
        $lastPrice = $prices->last();
        if ($lastPrice && ! Carbon::parse($lastPrice->time)->isFuture($now)) {
            $last = new TokenPrice([
                'time'  => $now->toDateTimeString(),
                'price' => $lastPrice->price,
            ]);
            $prices->push($last);
        }
        $data = [[
            'name' => '价格',
            'data' => $prices->map(function (TokenPrice $price) {
                return [
                    'x' => Carbon::parse($price->time, 'UTC')->timestamp * 1000,
                    'y' => removeTrailingZeros($price->price),
                ];
            })->toArray(),
        ]];

        $this->withData($data);
    }

    /**
     * 设置图表数据
     *
     *
     * @return $this
     */
    public function withData(array $data)
    {
        return $this->option('series', $data);
    }

    public function setGrid(Grid $grid)
    {
        $this->grid = $grid;

        return $this;
    }

    /**
     * 渲染图表
     *
     * @return string
     */
    public function render()
    {
        $this->buildData();

        return parent::render();
    }
}
