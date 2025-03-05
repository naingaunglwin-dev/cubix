<?php

/*
 |------------------------------------------------------
 | Bootstrap Application
 |------------------------------------------------------
 |
 | Bootstraps the application, initializing components
 | and preparing it for execution. Additional steps like
 | autoloading or exception handling can be added.
 |
 */
return new \Cubix\Foundation\Bootstrap()
    //->before() # add logic here to resolve before export
    //->autoload() # autoload files
    //->exception() # setup global exception handling
    ->export(); # export the application after bootstrapping
