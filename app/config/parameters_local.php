<?php

if (!isset($parameters)) {
    $parameters = [];
}

if (!empty($parameters['brick_builder_enable'])) {
    $parameters['theme_email_default'] = 'blank-as-email';
}
