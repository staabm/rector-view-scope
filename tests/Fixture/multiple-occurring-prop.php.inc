<?php

var_dump($hello);
if ($unknown) {
    print $hello;
}
call_some_crazy_func($hello);

-----
<?php

/**
 * @var string $hello
 */
var_dump($hello);
if ($unknown) {
    print $hello;
}
call_some_crazy_func($hello);
