<?php

use Ferienpass\FixturesBundle\Story\GlobalStory;

require dirname(__DIR__).'/vendor/autoload.php';

Zenstruck\Foundry\Test\TestState::addGlobalState(function () {
    GlobalStory::load();
});
