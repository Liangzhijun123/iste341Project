<?php
class Template {
    public static function render($title, $content) {
        echo "<!DOCTYPE html>";
        echo "<html lang='en'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<title>$title</title>";
        echo "<link rel='stylesheet' href='../assets/style.css'>";
        echo "</head><body>";
        echo "<header><h1>$title</h1></header>";
        echo "<main>$content</main>";
        echo "<footer>Bug Tracker &copy; 2026</footer>";
        echo "</body></html>";
    }
}