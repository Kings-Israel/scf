@extends('errors::layout')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'You are not authorized to perform this action'))
