@extends('errors.layout')

@section('code', '403')
@section('title', 'Access denied')
@section('icon', '🚫')
@section('heading', 'You don’t have access to this page')
@section('message', $exception->getMessage() ?: 'Your account role doesn’t allow this action. If you think this is a mistake, please contact the admin.')