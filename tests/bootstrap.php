<?php

require dirname(__DIR__).'/vendor/autoload.php';

Zenstruck\Foundry\Test\TestState::addGlobalState(function () {
    \Ferienpass\FixturesBundle\Story\GlobalStory::load();
});
