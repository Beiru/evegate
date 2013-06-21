<?php
/**
 *
 * form to add api,  configure what to forward, etc
 *
 * working example here: http://ash-alliance.eu/evegate.php
 *
 *
 */


include('include/db.php');
include('include/api_calls.php');
include('include/config.php');


//process post requests - start

if ($_POST['mode'] == 'logout')
{
    $_SESSION['evegate'][0]          = array();
    $_SESSION['evegate']['loggedin'] = 0;
    $_POST                           = array();
}
if ($_POST['mode'] == 'config')
{
    $api_key = $_SESSION['evegate'][0];

    $condition = " key_id = '{$api_key['key_id']}' ";

    $data                 = array();
    $data['forward_mail'] = mysql_real_escape_string($_POST['email']);

    if (strtotime($api_key['premium']) > time())
    {
        $filters['fromsent'] = $_POST['fromsent'];
        $filters['fromcorp'] = $_POST['fromcorp'];
        $filters['fromally'] = $_POST['fromally'];

        foreach ($_POST as $pk => $pw)
        {
            if (substr($pk, 0, 4) == 'sub_')
            {
                $lid           = substr($pk, 4);
                $filters[$lid] = 1;
            }
        }

        $data['filters'] = serialize($filters);
    }

    UpdateData($data, 'api_keys', $condition);

    $api_key                         = GetRow("SELECT * FROM api_keys WHERE key_id = '{$api_key['key_id']}' AND v_code = '{$api_key['v_code']}' ");
    $_SESSION['evegate'][0]          = $api_key;
    $_SESSION['evegate']['loggedin'] = 1;
}
if ($_POST['mode'] == 'login')
{
    $_POST['vcode'] = mysql_real_escape_string($_POST['vcode']);
    $_POST['keyid'] = mysql_real_escape_string($_POST['keyid']);
    $api_key        = GetRow("SELECT * FROM api_keys WHERE key_id = '{$_POST['keyid']}' AND v_code = '{$_POST['vcode']}' ");

    //if  not in db, try to register
    if (!$api_key['key_id'])
    {
        $url        = 'https://api.eveonline.com/account/APIKeyInfo.xml.aspx?keyID=' . $_POST['keyid'] . '&vCode=' . $_POST['vcode'];
        $xml_object = simplexml_load_string(file_get_contents($url));
        $xml_array  = object2array($xml_object);

        $characterID   = $xml_array["result"]["key"]["rowset"]["row"]["@attributes"]["characterID"];
        $characterNAME = getUserNameByID($characterID);
        if (strlen($characterNAME) > 3)
        {
            $regdata = array('key_id' => $_POST['keyid'], 'v_code' => $_POST['vcode'], 'username' => $characterNAME);
            InsertData($regdata, 'api_keys');
            $api_key = GetRow("SELECT * FROM api_keys WHERE key_id = '{$_POST['keyid']}' AND v_code = '{$_POST['vcode']}' ");
        }
    }

    if (!$api_key['key_id'])
    {
        ?>
        <H3>Error! Bad api key.</H3>

        <?
        exit;
    }
    $_SESSION['evegate'][0]          = $api_key;
    $_SESSION['evegate']['loggedin'] = 1;

}

// process post requests - end

?>
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
</head>
<body>
<h2>Forwarding configuration</h2>
<?php

// login / register form
if ($_SESSION['evegate']['loggedin'] <> 1)
{
    ?>
    <p>
        If You want to register, create predefined <a
            href="https://support.eveonline.com/api/Key/CreatePredefined/52736"
            target="new" rel="nofollow">API key</a>, for exactly one character (not for account!).<br/>
        In next step, set Your real E-mail address. That's all.
    </p>

    <p>
        For managing simply use the same api key/vCode pair to login.
    </p>

    <h2><a id="login" name="login">Login or Register</a></h2>

    <form method="post" action="">
        <label>Key ID<input type="text" name="keyid" class="edit"/></label>
        <label>Verification Code<input type="text" name="vcode" class="edit"/></label>
        <label><input type="submit" style="text-align: center; width: 120px; padding: 2px"
                      value="Submit"/></label>
        <input type="hidden" name="mode" value="login"/>
    </form>

<?
}
else // api configure form
{
    $url        = 'https://api.eveonline.com/account/APIKeyInfo.xml.aspx?keyID=' . $_SESSION['evegate'][0]['key_id'] . '&vCode=' . $_SESSION['evegate'][0]['v_code'];
    $xml_object = simplexml_load_string(file_get_contents($url));
    $xml_array  = object2array($xml_object);

    $characterID   = $xml_array["result"]["key"]["rowset"]["row"]["@attributes"]["characterID"];
    $characterNAME = getUserNameByID($characterID);
    ?>

    <H1>Welcome back <?
        echo $_SESSION['evegate'][0]['username'];

        ?></H1>
    <form method="post" action="">
        <label>Key ID: <?= $_SESSION['evegate'][0]['key_id'] ?></label>
        <label>Verification Code: <?= substr($_SESSION['evegate'][0]['v_code'], 0, 16) ?>...</label>
        <label>email address: <input type="text" name="email" value="<?= $_SESSION['evegate'][0]['forward_mail'] ?>"
                                     class="edit"/></label>
        <?
        if (!$_SESSION['evegate'][0]['forward_mail'])
        {
            echo "Write here Your email address.";
        }
        ?>

        <h2>Filters (message types You want to exclude) </h2>

        <label>Sent folder: <input type="checkbox"
                                   name="fromsent" <? is_checked('fromsent', $_SESSION['evegate'][0]['filters']); ?>
                                   class="edit" value="1"/></label>
        <label>From Corp: <input type="checkbox"
                                 name="fromcorp" <? is_checked('fromcorp', $_SESSION['evegate'][0]['filters']); ?>
                                 class="edit" value="1"/></label>
        <label>From Ally: <input type="checkbox"
                                 name="fromally" <? is_checked('fromally', $_SESSION['evegate'][0]['filters']); ?>
                                 class="edit" value="1"/></label>

        <h2>Mailing lists *</h2>

        <?
        $url = 'https://api.eveonline.com/char/mailinglists.xml.aspx?characterID=' . $characterID . '&keyID=' . $_SESSION['evegate'][0]['key_id'] . '&vCode=' . $_SESSION['evegate'][0]['v_code'];
        $xml_object = simplexml_load_string(file_get_contents($url));
        $xml_array = object2array($xml_object);
        foreach ($xml_array["result"]["rowset"]["row"] as $lrow)
        {
            $lid   = $lrow['@attributes']['listID'];
            $lname = $lrow['@attributes']['displayName'];
            ?>
            <label><?= $lname ?>: <input type="checkbox"
                                         name="sub_<?= $lid ?>" <? is_checked($lid, $_SESSION['evegate'][0]['filters']); ?>
                                         class="edit" value="1"/></label>
        <?
        }
        ?>
        <input type="hidden" name="mode" value="config"/>
        <label><input type="submit" alue="Submit"/></label>
    </form>
    <br/>
    <br/>
    <br/>
    <hr/>

    <?
    // logout button
    ?>
    <form method="post" action="">
        <input type="hidden" name="mode" value="logout"/>
        <input type="submit" value="Exit and logout"/>
    </form>
<?
}
?>
<br/>
<br/>


</body>
</html>

