<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2020120900;
$plugin->requires  = 2018050800;
$plugin->cron = 0;
$plugin->component = 'qtype_digitalliteracy';
$plugin->maturity = MATURITY_BETA;
$plugin->release = '0.9.3';

$plugin->dependencies = array(
    'qbehaviour_interactive_for_digitalliteracy' => 2020120100
);
