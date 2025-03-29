<div class="list-item">
    <div class="row">
        @if($rows->total() > 0)
            @foreach($rows as $row)
                <div class="col-lg-4 col-md-6">
                    @include('Tour::frontend.layouts.search.loop-grid')
                </div>
            @endforeach
        @else
            <div class="col-lg-12">
                {{__("Tour not found")}}
            </div>
        @endif
    </div>
</div>
<div class="bravo-pagination">
    {{$rows->appends(request()->except(['_ajax']))->links()}}
    @if($rows->total() > 0)
        <span class="count-string">{{ __("Showing :from - :to of :total Tours",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()]) }}</span>
    @endif
</div>
