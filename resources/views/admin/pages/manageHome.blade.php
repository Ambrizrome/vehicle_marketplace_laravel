@extends('layouts.admin')
@section('title', 'Manage Home')
@section('content')

{{-- Plantilla: el formulario aún no envía datos; conectar el action a un endpoint cuando exista --}}
<h1>Manage Home Page</h1>
You can edit data of the home page <br />
<form action="#">
    <input type="text" name="Headline" placeholder="Enter the headline" /> <br />
    <textarea name="Subline"></textarea> <br />
    <button type="submit" >Save Changes</button>
</form>

@stop
