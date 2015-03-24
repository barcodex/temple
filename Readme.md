# Readme

Temple is a lightweight text processor, suitable for usage in MVC systems that favour thick controllers.

## Installation

Install Temple with Composer by adding a requirement into composer.json of your project:

```json
{
   "require": {
        "barcodex/temple": "*" 
   } 
}
```

or requiring it from the command line:

```bash
composer require barcodex/template:* 
```

## Basic Usage
 
Basically, Processor class is just a library of static methods that provide dynamic filling of texts with data.

The data that we pass to Processor methods, is just a normal PHP associative array, elements of which can be scalar values or other arrays.

```php
<?php

require_once "vendor/autoload.php";

use Temple\Processor;

$template = 'Hello, {{name.first}} {{name.last}}!';
$params = array('name' => array('first' => 'John', 'last' => 'Doe'));

print Processor::doText($template, $params)) . PHP_EOL;
```

As you can see from this snippet, tags are following the mustache form, pretty much like in Twig. 
However, unlike Twig, processing in Temple does not have any control flow. 
There are no conditional statement and loops, everything is flat.
It means that caller of Temple\Processor is responsible to prepare all the data for the templates and take care about subtemplates when required.

So, if you don't like the idea of fat controllers and dumb templates, look elsewhere. Actually, there is Twig which is very nice, try that.

However, if you are still here, here are the reasons why you would like the idea of dumb templates:

- You can use the same templates with different backends. 
- HTML designers don't need to know about control flow syntax. They still need to train themselves to have a blind eye on {{}} tags
- You can build your own caching and translation logic around Temple
- You can process templates with different tag delimiters if you want (look for doTextVariation() method)

Yeah, that may sound a lot like a DYI-kit and it actually what Temple is. It just makes a first bold step to decouple presentation layer of your from the backend code.
 
Any backend can use Temple right away if its controllers are fat enough to provide all the necessary data. 
You can even use the same templates with backends written in different languages. The concept was proofed with C# and Perl

## Modifiers

To make dump templates just a little bit more intelligent and load some burden off controllers to templates, you still can implement some formatting logic using modifiers.

This is the same good old technique you could see in Smarty and Django many years ago. You just set pipe after your template variable and then use one of supported modifiers.

Unlike Smarty, standard PHP function are not supported as modifiers in order to keep things cross-platform in theory. So, Temple ports for other languages are responsible to implement the same modifiers.

