<?php

http_response_code(200);
echo json_encode(array("message" => "Logged out."), JSON_PRETTY_PRINT);