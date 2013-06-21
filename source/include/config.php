<?php
/**
 * configuration
 */


//setup db connection - mysql
$setup['db_ip'] = '127.0.0.1';
$setup['db_user'] = 'db_user';
$setup['db_pass'] = 'db_password';
$setup['db_name'] = 'database_name';


$link = dbConnection($setup['db_ip'], $setup['db_user'], $setup['db_pass'], $setup['db_name']);


//configure from mail
$from_mail = 'evegate@domain.com';