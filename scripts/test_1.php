<?php

//$fileContents = file_get_contents('http://africanewlife.custhelp.com/euf/assets/images/ANLM_Logo_Black.jpg');

$imageData = base64_encode(file_get_contents('http://africanewlife.custhelp.com/euf/assets/images/ANLM_Logo_Black.jpg'));
echo $imageData;

