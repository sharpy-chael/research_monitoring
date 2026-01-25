<?php
/**
 * Simple PDF Text Extractor
 * Works without external libraries
 */

function extractTextFromPDF($filePath) {
    // Method 1: Try pdftotext command (works on Linux servers)
    if (function_exists('shell_exec') && !stristr(PHP_OS, 'WIN')) {
        $output = shell_exec("pdftotext " . escapeshellarg($filePath) . " - 2>&1");
        if (!empty($output) && !stripos($output, 'command not found')) {
            return $output;
        }
    }
    
    // Method 2: Basic PHP PDF reading (limited but works)
    try {
        $content = file_get_contents($filePath);
        
        // Extract text between stream objects
        $text = '';
        if (preg_match_all("/\(([^)]+)\)/", $content, $matches)) {
            $text = implode(' ', $matches[1]);
        }
        
        // Clean up
        $text = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        if (!empty(trim($text))) {
            return $text;
        }
    } catch (Exception $e) {
        // Continue to fallback
    }
    
    // Method 3: Fallback - return basic info
    $fileSize = filesize($filePath);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    
    return "PDF file uploaded successfully. File size: {$fileSizeMB} MB. Note: Detailed text analysis requires additional server configuration.";
}

?>

<!-- <?php
/**
 * Advanced Document Text Extractor
 * Supports PDF and DOCX with high accuracy
 */

// class AdvancedExtractor {
    
//     /**
//      * Extract text from PDF using multiple methods
//      */
//     public static function extractFromPDF($filePath) {
//         $text = '';
        
//         // Method 1: pdftotext command (most accurate on Linux)
//         if (function_exists('shell_exec') && !stristr(PHP_OS, 'WIN')) {
//             $output = @shell_exec("pdftotext -layout " . escapeshellarg($filePath) . " - 2>&1");
//             if (!empty($output) && !stripos($output, 'command not found') && strlen($output) > 100) {
//                 return self::cleanText($output);
//             }
//         }
        
//         // Method 2: Advanced PHP parsing
//         $text = self::parsePDFContent($filePath);
//         if (strlen($text) > 100) {
//             return self::cleanText($text);
//         }
        
//         // Method 3: Read as binary and extract visible text
//         $text = self::extractBinaryText($filePath);
        
//         return self::cleanText($text);
//     }
    
//     /**
//      * Extract text from DOCX
//      */
//     public static function extractFromDOCX($filePath) {
//         $text = '';
        
//         // DOCX is actually a ZIP file with XML inside
//         $zip = new ZipArchive();
        
//         if ($zip->open($filePath) === true) {
//             // Main document content is in word/document.xml
//             $xml = $zip->getFromName('word/document.xml');
            
//             if ($xml) {
//                 // Remove XML tags and get plain text
//                 $xml = str_replace('</w:p>', "\n\n", $xml); // Paragraphs
//                 $xml = str_replace('</w:t>', ' ', $xml);     // Text runs
//                 $text = strip_tags($xml);
//                 $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
//             }
            
//             $zip->close();
//         }
        
//         return self::cleanText($text);
//     }
    
//     /**
//      * Parse PDF content using advanced regex
//      */
//     private static function parsePDFContent($filePath) {
//         $content = file_get_contents($filePath);
//         $text = '';
        
//         // Extract text from PDF streams
//         if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
//             foreach ($matches[1] as $match) {
//                 // Extract text within parentheses
//                 if (preg_match_all('/\((.*?)\)/s', $match, $textMatches)) {
//                     $text .= implode(' ', $textMatches[1]) . ' ';
//                 }
                
//                 // Extract text within brackets
//                 if (preg_match_all('/\[(.*?)\]/s', $match, $textMatches)) {
//                     foreach ($textMatches[1] as $t) {
//                         if (preg_match_all('/\((.*?)\)/', $t, $innerMatches)) {
//                             $text .= implode(' ', $innerMatches[1]) . ' ';
//                         }
//                     }
//                 }
//             }
//         }
        
//         // Fallback: simple extraction
//         if (empty(trim($text))) {
//             preg_match_all('/\(([^)]+)\)/', $content, $matches);
//             $text = implode(' ', $matches[1]);
//         }
        
//         return $text;
//     }
    
//     /**
//      * Extract binary text from PDF
//      */
//     private static function extractBinaryText($filePath) {
//         $content = file_get_contents($filePath);
        
//         // Remove binary junk, keep readable text
//         $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\xFF]/', ' ', $content);
        
//         // Extract words (sequences of letters)
//         preg_match_all('/[a-zA-Z]{3,}/', $text, $matches);
        
//         return implode(' ', $matches[0]);
//     }
    
//     /**
//      * Clean extracted text
//      */
//     private static function cleanText($text) {
//         // Replace escape sequences
//         $text = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)'], ["\n", "\r", "\t", '(', ')'], $text);
        
//         // Remove excessive whitespace
//         $text = preg_replace('/[ \t]+/', ' ', $text);
//         $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
//         // Remove control characters
//         $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', '', $text);
        
//         // Decode entities
//         $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        
//         return trim($text);
//     }
    
//     /**
//      * Main extraction method - auto-detects file type
//      */
//     public static function extract($filePath) {
//         $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
//         if ($extension === 'pdf') {
//             return self::extractFromPDF($filePath);
//         } elseif ($extension === 'docx') {
//             return self::extractFromDOCX($filePath);
//         } elseif ($extension === 'doc') {
//             // Old .doc format is more complex, fallback to basic extraction
//             return "DOC format detected. Please convert to DOCX or PDF for better analysis.";
//         }
        
//         return "Unsupported file format: {$extension}";
//     }
// }
?> -->