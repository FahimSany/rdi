<?php
require_once __DIR__.'/../includes/db.php';
require_admin();
json_response(array('error'=>'Legacy Join Applications are disabled. Use RDI Member IDs and controlled registration instead.'),410);
