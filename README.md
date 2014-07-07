Former [![Build Status](https://travis-ci.org/jarnstedt/former.svg?branch=master)](https://travis-ci.org/jarnstedt/former)
======

Laravel 4 form builder with Twitter Bootstrap styling. Extends Laravels own form builder.

* Set form defaults
* Repopulate forms
* Show validation errors

### Install
Add this to composer.json and run `composer update`.
```composer
"require": {
   "jarnstedt/former": "dev-master"
},
```

Add this to Laravel `providers` array in app/config/app.php file. 
```php
'Jarnstedt\Former\FormerServiceProvider',
```

Add this to `aliases` array.
```php
'Former' => 'Jarnstedt\Former\FormerFacade'
```

### Usage
Example form
```php
// controller
$user = User::find(1);
$form = Former::make($user);

// view
{{ $form->open() }}
{{ $form->text('name', 'Your name.req', 'default value', array('class' => 'className')) }}
{{ $form->submit('Save') }}
{{ $form->close() }}
```
