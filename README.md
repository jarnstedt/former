Former
======

Laravel 4 form builder with Twitter Bootstrap styling. Extends Laravels own form builder.


### Usage
Example form
```php
$form = Former::make(User::find(1));
{{ $form->open() }}
{{ $form->text('name', 'Name.req', 'default value', array('class' => 'name')) }}
{{ $form->submit('Save') }}
{{ $form->close() }}
```
