<?php
$hash = '$2y$10$SQtpqb2Um.GCfv3gDlq.8.ngOF.pNeIBWsd.KONpdsRLMH0qUmTjq';
$pass = 'rrahmat123';
if (password_verify($pass, $hash)) {
    echo "VERIFY SUCCESS\n";
} else {
    echo "VERIFY FAILED\n";
}
?>