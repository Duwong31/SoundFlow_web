@php
    $user = Auth::user();
    $languages = \Modules\Language\Models\Language::getActive();
@endphp

@if(!empty($languages) && setting_item('site_enable_multi_lang') && setting_item('site_locale'))
    <div class="btn-group" role="group" aria-label="Basic example">
        @foreach($languages as $language)
            <a
                class="btn  @if(request()->lang == $language->locale or (!request()->lang && $language->locale == setting_item('site_locale'))) btn-primary @else btn-default @endif"
                href="{{add_query_arg(['lang'=>$language->locale])}}"
            >
                @if($language->flag)
                    <span class="flag-icon flag-icon-{{$language->flag}}"></span>
                @endif
                {{$language->name}}
            </a>
        @endforeach
    </div>
    @if(request()->query('lang'))
        <input type="hidden" name="lang" value="{{request()->query('lang')}}">
    @endif
@endif
