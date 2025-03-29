@extends('errors.illustrated-layout',['title'=>__('Page not found')])
@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __($exception->getMessage() ?: 'Service Unavailable'))
