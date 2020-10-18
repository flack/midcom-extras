<?php
/**
 * Setup file for running unit tests
 */
require dirname(__DIR__) . '/vendor/openpsa/midcom/test/utilities/autoload.php';

openpsa_test_setup(__DIR__);

$GLOBALS['midcom_config_local'] = [
    'midcom_components' => [
        'midcom.helper.datamanager2' => dirname(__DIR__) . '/lib/midcom/helper/datamanager2'
    ],
    'midcom_config_basedir' => __DIR__
];

// Check that the environment is a working one
if (!midcom_connection::setup(dirname(__DIR__) . DIRECTORY_SEPARATOR))
{
    // if we can't connect to a DB, we'll create a new one
    openpsa\installer\midgard2\setup::install(OPENPSA_TEST_ROOT . '__output', 'SQLite');

    require dirname(__DIR__) . '/vendor/openpsa/midcom/tools/bootstrap.php';
    $GLOBALS['midcom_config_local']['log_level'] = 5;
    $GLOBALS['midcom_config_local']['log_filename'] = dirname(midgard_connection::get_instance()->config->logfilename) . '/midcom.log';
    $GLOBALS['midcom_config_local']['midcom_root_topic_guid'] = openpsa_prepare_topics();
    $GLOBALS['midcom_config_local']['auth_backend_simple_cookie_secure'] = false;
    $GLOBALS['midcom_config_local']['toolbars_enable_centralized'] = false;
}

//Get required helpers
require_once dirname(__DIR__) . '/vendor/openpsa/midcom/test/utilities/bootstrap.php';
