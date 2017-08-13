<?php
use PHPUnit\Framework\TestCase;

/**
*@covers Utilities
*/
class UtilitiesTest extends TestCase {


    /**
    * @test
    */
    public function test_is_assoc_Failed(){
        $array = ["this","is","a","test"];
    }

    public function test_is_assoc_Success(){
        $array = ["this"=>"is","a"=>"test"];
    }
}