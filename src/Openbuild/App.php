<?php

namespace Openbuild;

require '/composer-files/vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application('Food Standards Agency Data', '1.0.0');

$menuCommand = new Menu();

$application->add($menuCommand);
$application->add(new SchemeTypes());
$application->add(new Ratings());
$application->add(new Locations());
$application->add(new Authorities());
$application->add(new ScoreDescriptors());
$application->add(new BusinessTypes());
$application->add(new Establishments());

$application->setDefaultCommand($menuCommand->getName());

$application->run();
