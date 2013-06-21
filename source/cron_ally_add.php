<?php
/**
 *
 * use it once a day - maybe there is a new alliance
 *
 * |
 * | #cronjob example:
 * | #
 * | # m h d M W
 * |   0 1 * * * www-data /usr/bin/php5 -f /home/www/evegate/cron/cron_ally_add.php
 * |
 * |
 *
 */


include('include/db.php');
include('include/api_calls.php');
include('include/config.php');

$url        = 'https://api.eveonline.com/eve/AllianceList.xml.aspx?version=1';
$xml_object = simplexml_load_string(file_get_contents($url));
$xml_array  = object2array($xml_object);

foreach ($xml_array['result']['rowset']['row'] as $ally)
{
    $temp['org'][$ally['@attributes']['allianceID']] = GetValue("SELECT `name` FROM `corp_ally_names` WHERE `id`=" . $ally['@attributes']['allianceID']);
    if (!$temp['org'][$ally['@attributes']['allianceID']])
    {
        $temp['org'][$ally['@attributes']['allianceID']] = $ally['@attributes']['name'];
        InsertData(array('typ'    => 'a',
                         'id'     => $ally['@attributes']['allianceID'],
                         'name'   => $ally['@attributes']['name'],
                         'ticker' => $ally['@attributes']['shortName']),
                   'corp_ally_names');
    }
}
