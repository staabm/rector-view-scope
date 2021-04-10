<?php

var_dump($hello);
if ($unknown) {
    print $hello;
}
call_some_crazy_func($hello);
do_some_user_things($user);

-----
<?php

/**
 * @var string $hello
 * @var \UserVO $user
 */
var_dump($hello);
if ($unknown) {
    print $hello;
}
call_some_crazy_func($hello);
do_some_user_things($user);
