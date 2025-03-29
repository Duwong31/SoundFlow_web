@extends('errors.illustrated-layout',['title'=>__('Page not found')])
@section('title', __('Too Many Requests'))
@section('code', '429')
@section('message', __('Too Many Requests'))
