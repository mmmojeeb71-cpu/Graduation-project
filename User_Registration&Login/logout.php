<?php
session_start();
session_unset();
session_destroy();

// إعادة التوجيه مباشرة إلى صفحة index.html
header("Location: index.html");
exit();
