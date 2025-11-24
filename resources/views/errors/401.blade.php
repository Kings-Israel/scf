@extends('errors::layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('You are not authorized to view this page'))
