<?php
namespace OBSER\Classes;

abstract class Component{
    
    final static function get_class(){
         return substr(strrchr(static::class, "\\"), 1);
    }

    abstract static function map() : array;

}