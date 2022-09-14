<?php

namespace Custom\Controllers;

use RightNow\Connect\v1_3 as RNCPHP;

error_reporting(E_ALL);

class Convert extends \RightNow\Controllers\Base
{
    //This is the constructor for the custom controller. Do not modify anything within
    //this function.
    function __construct()
    {
        parent::__construct();
    }

    public function run($id)
    {
        try {

            $a = RNCPHP\sponsorship\Child::fetch(intval($id));

            // code to convert $a's properties and save
            // if (isset($a->Birthday)) {
            //     $birthday = new \DateTime();
            //     $birthday->setTimestamp($a->Birthday);
            //     $a->BirthYear = RNCPHP\standard\Year::first("Name = '{$birthday->format('Y')}'");
            //     $a->BirthMonth = RNCPHP\standard\Month::fetch(intval($birthday->format('n')));
            // }
            // if (isset($a->Priority)) {
            //     switch ($a->Priority) {
            //         case 1:
            //         case 2:
            //             $a->PriorityM =  RNCPHP\sponsorship\Priority::fetch(intval($a->Priority));
            //             $a->save();
            //             break;
            //     }
            // }

            if (isset($a->Community))
                $a->CommunityName = RNCPHP\standard\CommunityName::first("Name = '{$a->Community->Name}'");
            $a->save();
        } catch (\Exception $err) {
            print("<pre>{$err->getMessage()}</pre><br>");
            print("<code>Error on child ID: <bold>$id</bold></code><br>");
            // logMessage("Error on child ID:" . strval($id));
        } catch (RNCPHP\ConnectAPIErrorBase $er) {
            print("<pre>{$er->getMessage()}</pre><br>");
        }
    }

    public function runAll($limit = 0)
    {
        $limit = intval($limit);
        if ($limit > 0) {
            $query = "SELECT ID FROM sponsorship.Child LIMIT 10";
        } else {
            $query = "SELECT ID from sponsorship.Child";
            $query .= " WHERE sponsorship.Child.CommunityName IS NULL AND sponsorship.Child.Community.Name is not Null";
        }
        $children = RNCPHP\ROQL::query($query)->next();
        $count = 0;
        while ($child = $children->next()) {
            $this->run($child['ID']);
            $count++;
        }

        print("<code>$count children processed</code><br>");
    }

    public function run_as()
    {
        $this->run(8844);
    }
}
