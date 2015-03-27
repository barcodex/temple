# Readme

Temple is a lightweight text processor, suitable for usage in MVC systems that favour thick controllers.

Even though template processing assumes reading the template contents from somewhere, Temple is only works with the texts, leaving the task of implementing template reading/writing/caching to the libraries that use the library. 
If you are looking for an example of the project that implements Temple in this regard, take a look at [Templar](https://bitbucket.org/barcodex/templar)

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
composer require barcodex/temple:* 
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

print Processor::doText($template, $params) . PHP_EOL;
```

As you can see from this snippet, tags are following the mustache form, pretty much like in Twig. 
However, unlike Twig, processing in Temple does not have any control flow. 
There are no conditional statement and loops, everything is flat.
It means that caller of Temple\Processor is responsible to prepare all the data for the templates and take care about subtemplates when required.

So, if you don't like the idea of fat controllers and dumb templates, look elsewhere. Actually, there is Twig which is very nice, try that.

However, if you are still on this page, here are the reasons why you would like the idea of dumb templates:

- You can use the same templates with different backends. 
- HTML designers don't need to know about control flow syntax. They still need to train themselves to have a blind eye on {{}} tags
- You can build your own caching and translation logic around Temple
- You can process templates with different tag delimiters if you want (look for doTextVariation() method)

Yeah, that may sound a lot like a DYI-kit and it actually what Temple is. It just makes a first bold step to decouple presentation layer of your from the backend code.
 
Any backend can use Temple right away if its controllers are fat enough to provide all the necessary data. 
You can even use the same templates with backends written in different languages. The concept was proofed successfully with C# and Perl ports.

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

You will definitely want to add more modifiers, so probably, you will want to extend calculateValue() method of one or more Modifier-derived classes. If you make your own derivative of Modifier, you will need to extend Processor class, too, so that applyModifier() method uses the right classes.

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

Because overloaded version of the method first checks its own support modifiers and calls the parent method if no match was found, you can also provide your own versions of standard modifiers. 
For example, if you have special requirements for sanitization of SQL values in your queries, you can overload 'dbsafe' modifier.
Another good reason to extend standard modifiers is to inject profiling/logging/validation/notifications code. 

Here is how we would extend Processor in order to use the new Modifier class derivative:

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

| Modifier name  | Modifier subclass(es) | Type of output | Parameters | Comment |
| -------------- | --------------------- | -------------- | ---------- | ------- |
| iftrue (stopiffalse)   | * | empty string/original value |           | Keeps value intact if it evaluates to true, otherwise stops processing the pipeline and returns an empty string |
| iffalse (stopiftrue    | * | empty string/original value |           | Keeps value intact if it evaluates to false, otherwise stops processing the pipeline and returns an empty string |
| ifnull (stopifnotnull) | * | empty string/original value |           | Keeps value intact if it is null, otherwise stops processing the pipeline and returns an empty string |
| ifnotnull (stopifnull) | * | empty string/original value |           | Keeps value intact if it is not null, otherwise stops processing the pipeline and returns an empty string |
| htmlcomment    | *                     | string         |            | Wraps with HTML comment syntax |
| dump           | *                     | string         | pre        | Dumps the variable. optionally use "pre" to preserve line breaks when shown on HTML page |
| length         | ScalarModifier, ArrayModifier | int | | Depending on value, returns either length of the string or the size of the array |
| zero           | ScalarModifier        | string         |            | Explicitly converts an empty string to number zero |
| uppercase      | ScalarModifier        | string         |            | |
| lowercase      | ScalarModifier        | string         |            | |
| trim           | ScalarModifier        | string         |            | |
| htmlentities   | ScalarModifier        | string         |            | Escapes all HTML entities |
| nohtml         | ScalarModifier        | string         |            | Strips all HTML tags |
| loremipsum     | ScalarModifier        | string         |            | Injects some text placeholder aka Lorem Ipsum |
| fixurl         | ScalarModifier        | string         |            | makes sure that URL has http:// prefix |
| urlencode      | ScalarModifier        | string         |            | Encodes the URL |
| dbsafe         | ScalarModifier        | string         |            | Sanitizes the value to be injected into SQL query |
| jssafe         | ScalarModifier        | string         |            | Sanitizes the value to be used as JavaScript literal |
| htmlsafe       | ScalarModifier        | string         |            | Sanitizes the value to be used as HTML tag attribute value |
| shortener      | ScalarModifier        | string         | words: int, chars:int | Shortens the text to given number of words (or characters, if words param was not specified) |
| replace        | ScalarModifier        | string         | fallback: field name, default: string | Replaces value with another field (defined by 'default') from processing parameters. If fallback field is not found, 'default' value can be set |
| fixfloat       | ScalarModifier        | float          |            | Extracts a floating point number from the value |
| fixint         | ScalarModifier        | int            |            | Extracts an integer from the value |
| fixbool        | ScalarModifier        | bool           |            | Converts value to true or false |
| tag            | ScalarModifier        | string         |            | Wraps the value with mustache {{ }}, thus making a Temple tag out of it |
| wordcount      | ScalarModifier        | int            |            | Calculates number of words in the text |
| split          | ScalarModifier        | array          | delimiter: enum {'none' , 'space', 'comma',  'quotecomma', 'colon', 'semicolon', 'newline'} | Splits the string by a delimiter, provided as modifier parameter |
| unserialize    | ScalarModifier        | array/null     |            | Assumes that value is a JSON and tries to decode it. Null is returned is JSON could be decoded |
| gravatar       | ScalarModifier        | string         | size: int (default: 50) | Assumes that the value is an email and generates a url for gravatar image of given size |


As you can see, sometimes modifiers seem to be redundant, because of automatic type conversions that PHP does for us. 
But Temple syntax is designed to be backend-agnostic, so that controllers written in other programming languages could also work with the same templates.
That's why it is encouraged to tolerate this redundancy and use casting modifiers even if they seem unnecessary.
 
## Examples of modifiers
 
Even though we delegate most of the work to fat controllers, combining the modifiers makes it possible to calculate some values in the template and even to emulate some control flow.

### Calculating values

Imagine that list of categories for the blog post is stored as JSON string in the database field 'categories'. We can show the number of assign categories without special preparation of the value in the controller:

```html
<div class="blog-post-title">
    {{title}} 
    <span class="blog-post-categories-count">{{categories|unserialize|count}}</span>
</div>
```

### Faking the conditionals

So, there are no real IF-conditionals, but you can stop processing the modifiers pipeline and return an empty string, if your controllers pre-evaluated the boolean expressions.

Imagine that we want to show either user name or company name depending on client type and display this information using different styles. This can be done pretty easily with such template:

```html
<div class="client {{is_company|fixbool|iftrue|replace?default=company}}">{{display_name}}</div>
```

Placeholder called 'is_company' will be replaced with the word 'company' so that we could create a special CSS class called '.client.company' to show it differenty. 

Of course, this means that our controller should do the job preparing the values. We must prepare 'display_name' depending on company type and also provide 'is_company' field:

```php
$userData['is_company'] = ($userData['company_name'] != '');
$userData['display_name'] = ($userData['company_name'] == '') 
    ? $userData['first_name'] . ' ' . $userData['last_name']
    : $userData['company_name'];
```

In this example, using 'fixbool' modifier in the chain was just an example of HTML coder mistrusting the programmer of the controller ;) 
Actually, PHP implementation of Temple explicitly converts the value to a boolean before checking it with iftrue/iffalse modifiers. 
So if you trust PHP to convert nulls and empty strings to 'false', you can just use these modifiers without explicit typecast.
But if you are planning to re-use templates between different language implementations of the processor, it never hurts to be more explicit.

### Extension tips

This library is intentionally kept as simple as possible but easily extendable. Parametrizable modifiers allow to implement nice small snippets.
Imagine that you repeatedly do the same fixed formatting or micro-operation on data of specific kind - you can use modifiers with parameters to emulate functions that you would otherwise write in your controller. 
What is really cool, is that modifiers still have access to the whole array of data that was passed to a text processor that later parsed this particular {{tag}} with its modifier.

This makes possible to fake simple functions that use this data as inputs. 
For the sake of example, let's imagine a book shop that has its inventory in euros, but sells online also in US dollars and British pounds. 
We can easily write a modifier that prints out the price in session currency, automatically converting the price and adding a currency symbol. 
Let's also assume that static method getRate($fromCurrency, $toCurrency) of some CurrrencyRate class is already implemented, and the code to show original price looks like this:

```php
<?php

require_once "vendor/autoload.php";

use Temple\Processor;

$template = 'Book {{title}} by {{author}} costs EUR {{price}}';
$book = array('title' => 'Moby Dick', 'author' => 'Herman Melville', 'price' => 12);

print Processor::doText($template, $book)) . PHP_EOL;
```

So, we could implement the conversion every time when we prepare a value for processing like this:

```php
$template = 'Book {{title}} by {{author}} costs EUR {{price}}';
$book = array('title' => 'Moby Dick', 'author' => 'Herman Melville', 'price' => 12);
$book['price'] = ('EUR' == $_SESSION['currency']) 
    ? $book['price']
    : $book['price'] * CurrencyRate::getRate('EUR', $_SESSION['currency']);
```

but instead, we could move this to modifier:

```php
<?php

use Temple\ScalarModifier;

class BetterScalarModifier extends ScalarModifier 
{
    public static function calculateValue($modifierName, $modifierParams, $value, $params)
    {
        switch($modifierName) {
            case 'currency': 
                $price = ($_SESSION['currency'] == 'EUR') ? $value : $value * CurrencyRate::getRate('EUR', $_SESSION['currency']);
                $value = $_SESSION['currency'] . ' ' . $price;
                break;
            default:
                $value = parent::calculateValue($modifierName, $modifierParams, $value, $params);
        }
        
        return $value;
    }
}
```

and then in our templates we just use the new 'currency' modifier, freeing controller of the routine task of checking the session currency, converting the rate and printing out the right currency code:

```php
$template = 'Book {{title}} by {{author}} costs {{price|currency}}'; // note: no hardcoded currency code here
$book = array('title' => 'Moby Dick', 'author' => 'Herman Melville', 'price' => 12);
```


 