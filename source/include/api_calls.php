<?php
/**
 * eve api calls
 */


function object2array($object)
{
    return @json_decode(@json_encode($object), 1);
}


function getUserNameByID($id)
{
    if ($GLOBALS['user'][$id] == '')
    {
        $GLOBALS['user'][$id] = GetValue("SELECT `character_name` FROM `user_names` WHERE `character_id`=" . $id);
        if ($GLOBALS['user'][$id] == '')
        {
            $url = "https://api.eveonline.com/eve/CharacterName.xml.aspx?IDs={$id}";

            $xml_object = simplexml_load_string(file_get_contents($url));
            $xml_array  = object2array($xml_object);

            $characterName = $xml_array["result"]["rowset"]["row"]["@attributes"]["name"];
            InsertData(array('character_id' => $id, 'character_name' => $characterName), 'user_names');
            $GLOBALS['user'][$id] = $characterName;
        }
    }
    return $GLOBALS['user'][$id];
}

function getOrgNameByID($id)
{
    $org = GetRow("SELECT * FROM `corp_ally_names` WHERE `id`=" . $id);
    if (!$org['name'])
    {
        $url             = "https://api.eveonline.com/corp/CorporationSheet.xml.aspx?corporationID={$id}";
        $xml_object      = simplexml_load_string(file_get_contents($url));
        $xml_array       = object2array($xml_object);
        $corporationName = $xml_array["result"]["corporationName"];
        $org             = array('typ' => 'c', 'id' => $id, 'name' => $corporationName, 'ticker' => $xml_array["result"]['ticker']);
        InsertData($org, 'corp_ally_names');
    }
    return $org;
}


function getListNameByID($id)
{
    global $characterID, $keyID, $vCode;
    $list = GetValue("SELECT list_name FROM list_names WHERE list_id=$id");
    if (!$list)
    {
        $url        = "https://api.eveonline.com/char/mailinglists.xml.aspx?characterID={$characterID}&keyID={$keyID}&vCode={$vCode}";
        $xml_object = simplexml_load_string(file_get_contents($url));
        $xml_array  = object2array($xml_object);
        foreach ($xml_array["result"]["rowset"]["row"] as $lst){
            $lstdata = array('list_id' => $lst['@attributes']['listID'],'list_name' => $lst['@attributes']['displayName']);
            @InsertData($lstdata, 'list_names');
        }

    }
    $list = GetValue("SELECT list_name FROM list_names WHERE list_id=$id");
    return $list;
}

function is_checked($search,$string_data){
    $data = unserialize($string_data);
    if ($data[$search]==1){
        echo ' checked="checked"';
    }
}

function is_igb() {
    return isset($_SERVER['HTTP_EVE_TRUSTED']);
}