<?php
// Prevent Directory Listings
header( 'Status: 403 Forbidden' );
header( 'HTTP/1.1 403 Forbidden' );
exit;