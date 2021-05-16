<?php

class IndexController {
    /**
     * @var string
     */
    public $hello;
    /**
     * @var UserVO
     */
    public $user;

    public function home() {
        $this->hello = 'world';
    }
}