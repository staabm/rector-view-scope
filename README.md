# Problemspace

`ViewScopeRector` is your helper to make static analysis tools aware of variable-types within view-scripts infered from a external context. 

## Summary

This rector includes the mechanics of scanning procedural php files (e.g. views) and calling a given [ContextInferer](https://github.com/staabm/rector-view-scope/blob/main/lib/ContextInferer.php). It afterwards updates the view-files global `@var` phpdocs to reflect the types, the ContextInferer determinded beforehand.

A example implementation is shipped with [ViewContextInferer](https://github.com/staabm/rector-view-scope/blob/main/lib/inferer/rocket/ViewContextInferer.php), which implements Ruby'on Rails like view <–> controller type inference.

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

so in the end the rector will change the example view to
```php
/**
 * @var string
 */
echo $hello;
```
