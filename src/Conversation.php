<?php

namespace ChatApp;
require_once (dirname(__DIR__) . '/database.php');
use ChatApp\Time;
use ChatApp\User;

/**
*
*/
class Conversation
{
    protected $connect;
    protected $array;
    protected $obTime;
    protected $obUser;

    function __construct($sessionId)
    {
        session_id($sessionId);
        @session_start();
        $this->connect = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        session_write_close();
        $this->obTime = new Time();
        $this->obUser = new User();
        $this->array = array();
    }

    function ConversationLoad($msg)
    {

        $flag = 1;
        if(isset($_SESSION['start']) && isset($msg))
        {
            $add_load = 0;
            $id = $_SESSION['start'];
            $msg = json_decode($msg);
            $username = $msg->username;
            $load = $msg->load;

            $fetch = $this->obUser->UserDetails($username);
            if($fetch != NULL)
            {
                $login_id = (int)$fetch['login_id'];

                // Unique Identifier
                if($login_id > $id)
                    $identifier = $id.':'.$login_id;
                else
                    $identifier = $login_id.':'.$id;

                $query = "SELECT total_messages from total_message where identifier = '$identifier'";
                if($result = $this->connect->query($query))
                {
                    if($result->num_rows > 0)
                    {
                        $total = $result->fetch_assoc();
                        $total = $total['total_messages'];
                        if($total - $load > 0)
                            if($total - $load > 10)
                                $add_load = $load + 10;
                            else
                                $add_load = $total;
                    }
                }

                $query = "SELECT * FROM messages WHERE identifier_message_number = '$identifier' ORDER BY id DESC limit ".$load;
                if($result = $this->connect->query($query))
                {
                    if($result->num_rows > 0)
                    {
                        while($row = $result->fetch_assoc())
                        {
                            $row['time'] = $this->obTime->TimeConversion($row['time']);

                            $row['identifier_message_number'] = $login_id;
                            $row = array_merge($row,['name' => $fetch['name']]);
                            $row = array_merge($row,['login_status' => $fetch['login_status']]);
                            $row = array_merge($row,['start' => $id]);
                            $row = array_merge($row,['username' => $username]);
                            $this->array = array_merge($this->array, [$row]);
                        }
                        $this->array = array_merge($this->array, [['load' => $add_load]]);
                        $this->array = array_merge($this->array, [1]);
                        return json_encode($this->array);
                    }
                    else
                    {
                        return json_encode(['identifier_message_number' => $login_id, 'name' => $fetch['name'], 'login_status' => $fetch['login_status'], 'new' => 0]);
                    }
                }
                else
                {
                    echo "Query Failed";
                }
            }
            else
            {
                echo "Query Failed";
            }
        }
        else
        {
            header('Location:../login.php');
        }

        $this->connect->close();
    }
}
?>