<?php
require_once __DIR__.'/../includes/db.php';
json_response(array(
  'error'=>'Public RDI registration is disabled. An administrator must first issue a one-time RDI Member ID linked to your approved email. Use the Member Sign Up page.'
),410);
