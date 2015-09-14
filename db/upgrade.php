<?php

function xmldb_local_autotagger_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2015080904) {

        // Define key lang_unique (unique) to be added to local_autotagger.
        $table = new xmldb_table('local_autotagger');
        $key = new xmldb_key('lang_unique', XMLDB_KEY_UNIQUE, array('language'));

        // Launch add key lang_unique.
        $dbman->add_key($table, $key);

        // Autotagger savepoint reached.
        upgrade_plugin_savepoint(true, 2015080904, 'local', 'autotagger');
    }

    return true;
}