@extends('errors.illustrated-layout',['title'=>__('Page not found')])
@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Forbidden'))
