# Readme

Temple is a lightweight text processor, suitable for usage in MVC systems that favour thick controllers.

## Installation

Install Temple with Composer:

```json
{
   "require": {
        "barcodex/temple": "*" 
   } 
}
```

or from the command line:

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

$params = array('name' => array('first' => 'John', 'last' => 'Doe'));
print 
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

Yeah, that may sound a lot like a DYI-kit and it actually what Temple is. It just makes a first bold step to decouple presentation layer of your from the backend code.
 
Any backend can use Temple right away if its controllers are fat enough to provide all the necessary data. 
You can even use the same templates with backends written in different languages. The concept was proofed with C# and Perl