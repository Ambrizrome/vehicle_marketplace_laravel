@extends('layouts.admin')
@section('title', 'Manage Contact')
@section('content')

{{-- Plantilla: el formulario aún no envía datos; conectar el action a un endpoint cuando exista --}}
<h1>Manage Contact Page</h1>
You can edit the data of the contact page <br />
<form action="#">
    <textarea name="Content"></textarea> <br />
    <input type="text" name="Adress" placeholder="Enter a adress" /> <br />
    <input type="text" name="PhoneNumber" placeholder="Enter a phone number" /> <br />
    <button type="submit" >Save Changes</button>
</form>

@stop
