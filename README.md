# Problemspace

`ViewScopeRector` is your helper to make static analysis tools aware of variable-types within view-scripts infered from a external context. 

## example big picture

this rector is meant to introduce `@var` phpdocs into analyzed view files, e.g. based on declared public properties of a corresponding controller.

example Controller:
```php
class Controller {
   /**
    * @var string
    */
   public $hello;
}
```

example view:
```php
echo $hello;
```

the rector should lookup the controller-class via static reflection, [infer the type of its properties](https://github.com/staabm/rector-view-scope/blob/main/lib/ContextInferer.php) and with this knowledge adjust/create a `@var` phpdoc in the view file.

so in the end the rector should change the example view to
```php
/**
 * @var string
 */
echo $hello;
```
