@extends('layouts.admin')
@section('title', 'Manage Skills')
@section('content')

{{-- Plantilla: los formularios aún no envían datos; conectar el action a un endpoint cuando exista --}}
<h1>Manage Skills Page</h1>
<h1>Add A New Skill</h1>
You can add a new skill here <br />
<form action="#">
    <input type="text" name="skill" placeholder="Enter a new skill" /> <br />
    <textarea name="description" placeholder="Enter a description for the skill" ></textarea> <br />
    <button type="submit" >Add The Skill</button>
</form>
<hr>

<h1>Edit Your Skills</h1>
You can edit the data of the skills page <br />

{{-- Elemento de ejemplo; aquí iría un @foreach con los datos reales --}}
<div>
<form action="#">
    <input type="text" name="skill" placeholder="Enter the skill" /> <br />
    <textarea name="description" placeholder="Enter the description of the skill" ></textarea>
    <button type="submit" name="action" value="update" >Update</button>
    <button type="submit" name="action" value="delete" >Delete</button>
</form>
</div>

@stop
