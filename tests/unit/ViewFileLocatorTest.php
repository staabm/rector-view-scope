<?php

final class ViewFileLocatorTest extends \PHPUnit\Framework\TestCase {
    public function testIndexController():void {
        $locator = new \ViewScopeRector\Inferer\Rocket\ViewFileLocator('app/views/index/index.php');
        $this->assertTrue($locator->isInViewPath());
        $this->assertTrue($locator->isTopLevelView());
        $this->assertSame('\IndexController', $locator->findMatchingController());
    }

    public function testAdmgrpController():void {
        $locator = new \ViewScopeRector\Inferer\Rocket\ViewFileLocator('app/views/admgrp/index.php');
        $this->assertTrue($locator->isInViewPath());
        $this->assertTrue($locator->isTopLevelView());
        $this->assertSame('\AdmgrpController', $locator->findMatchingController());
    }

    public function testViewComponent():void {
        $locator = new \ViewScopeRector\Inferer\Rocket\ViewFileLocator('app/views/admgrp/_component.php');
        $this->assertTrue($locator->isInViewPath());
        $this->assertFalse($locator->isTopLevelView());
        $this->assertNull($locator->findMatchingController());
    }

    public function testForeignFile():void {
        $locator = new \ViewScopeRector\Inferer\Rocket\ViewFileLocator('app/some/other.php');
        $this->assertFalse($locator->isInViewPath());
        $this->assertFalse($locator->isTopLevelView());
        $this->assertNull($locator->findMatchingController());
    }
}
