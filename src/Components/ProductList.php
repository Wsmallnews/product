<?php

namespace Wsmallnews\Product\Components;

use Closure;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Wsmallnews\Product\Models\Product;


class ProductList extends Component
{
    use WithPagination;
    use WithoutUrlPagination;

    public int | string $pageName = 'page';

    public int | string $perPage = 10;

    public string $pageType = 'scroll';      // scroll:滚动加载更多,paginator:分页器,manual:手动

    public Collection $products;

    public array $pageInfo = [];

    public string $cardView = 'sn-product::product-cards.simple';

    public string $detailRouteName;

    public function mount()
    {
        $this->products = $this->products ?? collect([]);
    }


    public function render()
    {
        $current = Product::query()->scopeInfo('default', 0);

        if ($this->pageType == 'paginator') {
            $current = $current->paginate($this->perPage, pageName: $this->pageName);
            $this->products = $current->getCollection();        // 获取 collection 格式的数据
        } else {
            $current = $current->simplePaginate($this->perPage, pageName: $this->pageName);
            $this->products = $this->products->merge($current->items());
        }

        // 分页信息
        $this->pageInfo = [
            'count' => $current->count(),                                       // 当前查询最终的结果数量
            'per_page' => $current->perPage(),                                  // 每页条件
            'current_page' => $current->currentPage(),                          // 当前页码
            'load_status' => 'loading',                                         // 默认加载中
            'is_last_page' => 0,                                                // 默认不是最有一页
        ];

        if ($this->pageType == 'paginator') {
            $this->pageInfo['total'] = $current->total();                  // 满足条件总条数
            $this->pageInfo['last_page'] = $current->lastPage();           // 最后的页码

            if ($this->pageInfo['current_page'] >= $this->pageInfo['last_page']) {
                $this->pageInfo['is_last_page'] = 1;
                $this->pageInfo['load_status'] = 'nomore';

                if ($this->pageInfo['current_page'] == 1 && $this->pageInfo['count'] <= 0) {
                    $this->pageInfo['load_status'] = 'empty';
                }
            }
        } else {
            if ($this->pageInfo['count'] < $this->pageInfo['per_page']) {
                $this->pageInfo['is_last_page'] = 1;
                $this->pageInfo['load_status'] = 'nomore';

                if ($this->pageInfo['current_page'] == 1 && $this->pageInfo['count'] <= 0) {
                    $this->pageInfo['load_status'] = 'empty';
                }
            }
        }

        return view('sn-product::livewire.products.index', [
            'paginatorLink' => $current->links()
        ])->title('产品列表');
    }
}
