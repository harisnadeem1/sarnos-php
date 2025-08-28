<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language = Dutch
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = "nl";
}

// If user clicks ?lang=fr or ?lang=nl
if (isset($_GET['lang'])) {
    $selectedLang = $_GET['lang'];
    if (in_array($selectedLang, ["nl", "fr"])) {
        $_SESSION['lang'] = $selectedLang;
    }
}

// Load JSON file
$lang = $_SESSION['lang'];
$langFile = __DIR__ . "/lang/$lang.json";

if (file_exists($langFile)) {
    $jsonData = file_get_contents($langFile);
    $texts = json_decode($jsonData, true);
} else {
    $texts = [];
}
?>
