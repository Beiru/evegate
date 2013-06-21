<?php
/**
 *
 * use it every 10 minutes
 *
 * |
 * | #cronjob example:
 * | #
 * | # m h d M W
 * |   0,10,20,30,40,50 * * * * www-data /usr/bin/php5 -f /home/www/evegate/cron/cron_messages_forward.php
 * |
 * |
 */


include('include/db.php');
include('include/api_calls.php');
include('include/config.php');


function SendMail($to1, $from, $subject, $message, $charset = "utf-8")
{

    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=' . $charset . "\r\n";
    $headers .= 'From: ' . $from . "\r\n";
    mail($to1, $subject, $message, $headers);
}


$api_keys_array = GetResult("SELECT * FROM api_keys WHERE key_id>0 and forward_mail>'' ");
foreach ($api_keys_array as $single_key)
{
    $keyID       = $single_key["key_id"];
    $vCode       = $single_key["v_code"];
    $forwardmail = $single_key["forward_mail"];
    $filters     = unserialize($single_key['filters']);


    //get api user
    $url        = 'https://api.eveonline.com/account/APIKeyInfo.xml.aspx?keyID=' . $keyID . '&vCode=' . $vCode;
    $xml_object = simplexml_load_string(file_get_contents($url));
    $xml_array  = object2array($xml_object);

    $characterID   = $xml_array["result"]["key"]["rowset"]["row"]["@attributes"]["characterID"];
    $characterNAME = getUserNameByID($characterID);


    //get mail headers

    $maildata = "https://api.eveonline.com/char/MailMessages.xml.aspx?characterID={$characterID}&keyID={$keyID}&vCode={$vCode}";

    $xml_object2 = simplexml_load_string(file_get_contents($maildata));
    $xml_array2  = object2array($xml_object2);


    if (count($xml_array2["result"]["rowset"]["row"]) && is_array($xml_array2["result"]["rowset"]["row"]))
    {
        foreach ($xml_array2["result"]["rowset"]["row"] as $mailitem)
        {
            $mail  = $mailitem["@attributes"];
            $check = GetValue("SELECT `message_id` FROM `processed_mails` WHERE `key_id` = $keyID AND `message_id` = {$mail["messageID"]}");
            if (!$check)
            {
                //init filter state
                $no_send = false;

                //don't send the same message twice
                InsertData(array('key_id' => $keyID, 'message_id' => $mail["messageID"]), 'processed_mails');


                //init mail title
                $mail_title = $mail["title"];

                //init list of mail recipients
                $to_list = '';

                if ($mail["toListID"] > '')
                {
                    if ($filters[$mail["toListID"]])
                    {
                        $no_send = true;
                    }
                    $list       = getListNameByID($mail["toListID"]);
                    $mail_title = '' . $list . ' // ' . $mail_title;
                    $to_list .= '<a style="font-weight:bold; color: #02FA46; text-decoration: none" href="https://gate.eveonline.com/Mail/MailingList/' .
                        $mail['toListID'] . '/">' . $list . '</a>, ';

                }
                if ($mail["toCorpOrAllianceID"] > '')
                {
                    $org  = getOrgNameByID($mail["toCorpOrAllianceID"]);
                    $name = $org['name'];
                    if ($org['typ'] == 'c')
                    {
                        if ($filters['fromcorp'])
                        {
                            $no_send = true;
                        }
                        $mail_title = 'CORP // ' . $mail_title;
                        $to_list .= '<a style="font-weight:bold; color: #fa9e0e; text-decoration: none" href="https://gate.eveonline.com/Corporation/' .
                            str_replace(' ', '%20', $org['name']) . '/">' . $org['name'] . ' [' .
                            $org['ticker'] . ']</a>, ';
                    }

                    if ($org['typ'] == 'a')
                    {
                        if ($filters['fromally'])
                        {
                            $no_send = true;
                        }
                        $mail_title = 'ALLY // ' . $mail_title;
                        $to_list .= '<a style="font-weight:bold; color: #fa9e0e; text-decoration: none" href="https://gate.eveonline.com/Alliance/' .
                            str_replace(' ', '%20', $org['name']) . '/">' . $org['name'] . ' [' .
                            $org['ticker'] . ']</a>, ';
                    }

                }
                if ($characterID == $mail["senderID"])
                {
                    if ($filters['fromsent'])
                    {
                        $no_send = true;
                    }
                    $mail_title = 'SENT // ' . $mail_title;
                }


                if ($mail["toCharacterIDs"] > '')
                {
                    $recipients = explode(',', $mail["toCharacterIDs"]);
                    foreach ($recipients as $character)
                    {
                        if ($character <> $mail["senderID"])
                        {
                            $name = getUserNameByID($character);
                            $to_list .= '<a style="color: #fa9e0e; text-decoration: none" href="https://gate.eveonline.com/Profile/' .
                                str_replace(' ', '%20', $name) . '/">' . $name . '</a>, ';
                        }
                    }
                }

                //get mesage body from api
                $mailbody      = "https://api.eveonline.com/char/MailBodies.xml.aspx?ids={$mail["messageID"]}&characterID={$characterID}&keyID={$keyID}&vCode={$vCode}";
                $xml_mailbody  = simplexml_load_string(file_get_contents($mailbody), null, LIBXML_NOCDATA);
                $xml_amailbody = object2array($xml_mailbody);
                $body          = $xml_amailbody['result']['rowset']['row'];

                //strip questionable things
                $body = nl2br(strip_tags($body, '<a><b><br><p>'));
                $body = str_replace("<a href=", "<a style=\"color: #fa9e0e; text-decoration: none\" href=", $body);


                //eve style formatted mail body
                $mail_body = '
            <div style="color: #CCCCCC; background-color: #222222; padding: 15px">
                <div style="position: relative; text-align: left; margin: 0 auto; font-size: 12px; width: 620px; padding: 20px; background-color: #333333;">
                    <div style="position: absolute; right: 0px; top: 0px;">
                        <p style="margin: 0px; color: #898989;"> ' . $mail["sentDate"] . '</p>
                    </div>
                    <h2 style="font-size: 18px; font-weight: normal; width: 620px;">' . $mail["title"] . '</h2>
                    <p style="margin: 0px; color: #898989;">
                        <span style="font-weight: bold;">From:</span> <a style="color: #fa9e0e; text-decoration: none" href="https://gate.eveonline.com/Profile/' .
                    str_replace(' ', '%20', getUserNameByID($mail["senderID"])) . '">' . getUserNameByID($mail["senderID"]) . '</a><br>
                        <span style="font-weight: bold;">To:</span> ' . $to_list . '
                    </p>
                  <br>
                    ' . $body . '
                    <br />
                    <br />
                    <br />
                    <hr />
                    <a style="font-weight:bold; color: #fa9e0e; text-decoration: none" href="https://gate.eveonline.com/Mail/ReadMessage/' .
                    $mail["messageID"] . '/">If You want to reply, go to eve gate...</a>
                </div>
            </div>
            ';

                //if there was no filter conditions met - send it
                if (!$no_send)
                {
                    SendMail("$characterNAME<$forwardmail>", getUserNameByID($mail["senderID"]) . "<{$from_mail}>", $mail_title, $mail_body);
                }

            }
        }
    }
}