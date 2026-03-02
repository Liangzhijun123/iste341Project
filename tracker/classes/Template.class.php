<?php
/**
 * Template Class
 * Provides a centralized rendering engine for the application's user interface.
 * This class ensures a consistent look and feel by wrapping page-specific 
 * content in a standard HTML5 structure with a global header and footer.
 */
class Template {
    /**
     * Renders a complete HTML page.
     * * @param string $title The text to display in the <title> tag and the page header.
     * @param string $content The main HTML body content to be injected into the <main> tag.
     */
    public static function render($title, $content) {
        // Output the document type and opening tags
        echo "<!DOCTYPE html>";
        echo "<html lang='en'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<title>$title</title>";
        
        // Link to the centralized CSS for global styling
        echo "<link rel='stylesheet' href='../assets/style.css'>";
        echo "</head><body>";

        // Global Page Header
        echo "<header><h1>$title</h1></header>";

        // Primary dynamic content area
        echo "<main>$content</main>";

        // Standardized footer for the 2026 academic project
        echo "<footer>Bug Tracker &copy; 2026</footer>";
        
        // Close the document
        echo "</body></html>";
    }
}