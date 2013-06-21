<?php
/**
 *
 *  db fnc
 *
 */


function ExecQuery($sql)
{
    $result = mysql_query($sql);
    if (!mysql_error())
    {
        $result_bool = true;
    }
    else
    {
        $result_bool = false;
    }
    return ($result_bool);
}

function dbConnection($ip, $user, $pass, $name)
{
    $link = mysql_connect($ip, $user, $pass);
    mysql_select_db($name, $link);
    return $link;
}

function InsertData($data, $table)
{
    $fields = '';
    $values = '';
    if ((is_array($data))and(count($data) > 0))
    {
        foreach ($data as $key => $value)
        {
            $fields .= $key . ", ";
            if (is_array($value))
            {
                $values .= current($value) . ", ";
            }
            else
            {
                $values .= "'" . addslashes($value) . "', ";
            }
        }
    }
    $fields = preg_replace("/, $/", "", $fields);
    $values = preg_replace("/, $/", "", $values);
    $query  = "INSERT INTO " . $table . "(" . $fields . ") VALUES(" . $values . "); ";
    ExecQuery($query);
    return (mysql_insert_id());
}

function UpdateData($data, $table, $where)
{
    $query = 'UPDATE ' . $table . " SET ";
    foreach ($data as $key => $value)
    {
        if (is_array($value))
        {
            $query .= $key . "=" . current($value) . ", ";
        }
        else
        {
            $query .= $key . "='" . addslashes($value) . "', ";
        }
    }
    $query = preg_replace("/, $/", "", $query);
    $query .= " WHERE " . $where;
    ExecQuery($query);

    return (mysql_affected_rows());
}

function GetValue($sql)
{
    $result = mysql_query($sql);

    $rows_count = @mysql_num_rows($result);
    if ($rows_count == 1)
    {
        $row = @mysql_fetch_row($result);
    }
    @mysql_free_result($result);
    return (stripcslashes(@$row[0]));
}

function GetRow($sql)
{
    $result = mysql_query($sql);

    if (!$result)
    {
        return false;
    }

    $rows_count = mysql_num_rows($result);
    if ($rows_count == 1)
    {
        $row = StripSlashesRow(mysql_fetch_array($result));
    }
    $row_tmp = @$row;
    for ($i = 0; $i < count($row_tmp); $i++)
        unset($row_tmp[$i]);

    $row = $row_tmp;
    return ($row);
}

function StripSlashesRow($row)
{
    if (count($row))
    {
        foreach ($row as $key => $value)
            $row_upd[$key] = stripslashes($value);
        return ($row_upd);
    }
    else
    {
        return ($row);
    }
}

function GetResult($sql)
{
    $row = array();

    $result = mysql_query($sql);
    if (!mysql_error())
    {
        $rows_count = @mysql_num_rows($result);
        if ($rows_count)
        {
            for ($j = 0; $j < $rows_count; $j++)
            {
                $row_tmp = mysql_fetch_array($result, MYSQL_ASSOC);
                $row_tmp = StripSlashesRow($row_tmp);
                $row[$j] = $row_tmp;
            }
        }
    }
    @mysql_free_result($result);
    return ($row);
}




