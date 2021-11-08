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
    /**
     * @var UserVO::MODE_*
     */
    protected $moduleMode;

    public function home() {
        $this->hello = 'world';
    }
}