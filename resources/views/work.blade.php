<?php
   dump(session('token'));
   dump(json_decode($result));
?>

@extends('layout.app')


@section('content-main')
    @include('flash')



    <a href="{{  route('init') }}">Get Token</a>
    <table class="table">
        <tr>
            <td><a href="{{ route('work', ['method' => 'getModules'])  }}">Get All Modules</a></td>
        </tr>
        <tr>
            <td><a href="{{ route('work', ['method' => 'getFieldsMetadata', 'module' => 'Leads'])  }}">Get fields metadata for Leads</a></td>
        </tr>
        <tr>
            <td><a href="{{ route('work', ['method' => 'postValue', 'module' => 'Leads'])  }}">Add New Lead</a></td>
        </tr>
        <tr>
            <td><a href="{{ route('work', ['method' => 'getAllRecords', 'module' => 'Deals'])  }}">Get All Deals</a></td>
        </tr>
        <tr>
            <td><a href="{{ route('work', ['method' => 'getFieldsMetadata', 'module' => 'Deals'])  }}">Get fields metadata for Deals</a></td>
        </tr>
        <tr>
            <td><a href="{{ route('work', ['method' => 'getFieldsMetadata', 'module' => 'Task'])  }}">Get fields metadata for Task</a></td>
        </tr>
        <tr>
            <td><a href="{{ route('work', ['method' => 'createDealWithTask', 'module' => 'Deals'])  }}">Create Dial with Task</a></td>
        </tr>
    </table>
@endsection
