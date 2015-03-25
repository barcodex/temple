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

Unlike Smarty, Temple does not support standard PHP functions as modifiers in order to keep things cross-platform in theory. So, Temple ports for other languages are responsible to implement the same modifiers.

Modifier can be changed, and this can be fun:

```html
<h2>{{name.first|shortener?chars=10|uppercase|htmlsafe}}</h2>
```

As you can see from this example, modifiers can have parameters, and we pass parameters as if they are URI query string parameters.

In some cases, type of the modified value is changed during the processing - and Temple ensures that the end result after applying the whole modifier chain is a string.

Suddenly, we realize that Temple could be used for preparing SQL statements, too!

```sql
UPDATE users SET fname='{{name.first|shortener?chars=50|dbsafe}}'
WHERE id = {{id|zero}}
```

So, even if you do not use some ORM, you still can easily decouple your controllers from the SQL code.

Processor::applyModifier() method applies modifiers from the chain one by one, choosing the right Modifier class to do the job. It decides which derivative of Modifier class to use, depending on the type of the value being modified. 

At the moment we distinguish 4 different cases:

- ArrayModifier - when base value is an array
- ObjectModifier - when base value is an object
- NumericModifier - when base value is a number
- ScalarModifier - when base value is a string

## Extending Temple

Goal of this library is to make a first step towards decoupling PHP code from presentation, so it is far from being comprehensive.

The good news is that it is extendable without too much difficulties. Some clues are given in the comments for Temple class itself, so you do not need to always come back to this document to refresh your memory.

You will definitely want to add more modifiers, so probably, you will want to extend calculateValue() method of one or more Modifier classes. If you make your own derivative of Modifier, you will need to extend Processor class, too, so that applyModifier() method uses the right classes.

It would look like this to extend a Modifier class:

```php
<?php

use Temple\ScalarModifier;

class BetterScalarModifier extends ScalarModifier 
{
    public static function calculateValue($modifierName, $modifierParams, $value, $params)
    {
        switch($modifierName) {
            case 'bark': // forget all values and modifiers and just bark
                $value = 'rrr auh auh auh';
                break;
            default:
                $value = parent::calculateValue($modifierName, $modifierParams, $value, $params);
        }
        
        return $value;
    }
}
```

And here is how we would extend Processor in order to use the new Modifier class derivative:

```php
<?php

use Temple\Processor;
use Temple\ObjectModifier;
use Temple\ArrayModifier;
use Temple\NumericModifier;

class BetterProcessor extends Processor 
{
	public static function applyModifier($value, $filters, $params = array()) 
	{
		if (is_object($value)) {
			return ObjectModifier::apply($value, $filters, $params);
		} else if (is_array($value)) {
			return ArrayModifier::apply($value, $filters, $params);
		} else if (is_numeric($value)) {
			return NumericModifier::apply($value, $filters, $params);
		} else {
			return BetterScalarModifier::apply($value, $filters, $params);
		}
	}
}
```

You see that we just replaced ScalarModifier with BetterScalarModifier here because it was the only extended class in our example.

## Reference of standard modifiers

Let's quickly take a look at all supported modifiers. They can be classified by the class that applies them, by the number of parameters they expect and by the type of the value they return.

| modifier name | Modifier subclass(es) | type of output | parameters | comment |
| ------------- | --------------------- | -------------- | ---------- | ------- |
| uppercase     | ScalarModifier        | string         |            |         |
| lowercase     | ScalarModifier        | string         |            |         |
| trim          | ScalarModifier        | string         |            |         |
| htmlentities  | ScalarModifier        | string         |            | Escapes all HTML entities |
| nohtml        | ScalarModifier        | string         |            | Strips all HTML tags |
| htmlcomment   | ScalarModifier        | string         |            | Wraps with HTML comment syntax |
 