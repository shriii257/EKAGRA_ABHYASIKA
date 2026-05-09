<?php
// includes/header.php
// NOTE: db.php already calls session_start(). Never call it again here.
// $page_title and $base must be set before including this file.
if (!isset($base)) {
    $base = (
        strpos($_SERVER['PHP_SELF'], '/admin/')   !== false ||
        strpos($_SERVER['PHP_SELF'], '/student/') !== false
    ) ? '../' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? e($page_title) . ' — ' : ''; ?>Ekagra Abhyasika</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body>
