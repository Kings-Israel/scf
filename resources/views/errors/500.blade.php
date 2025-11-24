@extends('errors::layout')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('An error occurred while processing your request.'))
