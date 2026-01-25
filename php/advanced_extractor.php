<?php
/**
 * ULTRA-ADVANCED Document Text Extractor v2.0
 * Dramatically improved extraction accuracy for PDF and DOCX
 * No external dependencies required
 */

class AdvancedExtractor {
    
    /**
     * Main extraction method - intelligently detects file type
     */
    public static function extract($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return self::extractFromPDF($filePath);
            case 'docx':
                return self::extractFromDOCX($filePath);
            case 'doc':
                return self::extractFromDOC($filePath);
            default:
                throw new Exception("Unsupported file format: {$extension}");
        }
    }
    
    /**
     * ULTRA-ACCURATE PDF Extraction with multiple fallback methods
     */
    public static function extractFromPDF($filePath) {
        $text = '';
        
        // Method 1: pdftotext (most reliable for Linux servers)
        if (self::isPDFToTextAvailable()) {
            $text = self::extractPDFWithPdfToText($filePath);
            if (strlen($text) > 100 && self::isValidText($text)) {
                return self::cleanText($text);
            }
        }
        
        // Method 2: Advanced PDF stream extraction (pure PHP)
        $text = self::extractPDFStreams($filePath);
        if (strlen($text) > 100 && self::isValidText($text)) {
            return self::cleanText($text);
        }
        
        // Method 3: Binary extraction (last resort)
        $text = self::extractBinaryText($filePath);
        
        if (strlen($text) < 100) {
            throw new Exception("PDF extraction failed - document may be scanned, encrypted, or image-based. Please try converting to text format first.");
        }
        
        return self::cleanText($text);
    }
    
    /**
     * Check if pdftotext command is available
     */
    private static function isPDFToTextAvailable() {
        if (stristr(PHP_OS, 'WIN')) {
            return false; // Usually not available on Windows
        }
        
        $output = @shell_exec('which pdftotext 2>&1');
        return !empty($output) && !stripos($output, 'not found');
    }
    
    /**
     * Extract PDF using pdftotext command
     */
    private static function extractPDFWithPdfToText($filePath) {
        $escapedPath = escapeshellarg($filePath);
        
        // Try with layout preservation first
        $output = @shell_exec("pdftotext -layout -nopgbrk {$escapedPath} - 2>&1");
        
        if (!empty($output) && !stripos($output, 'error') && strlen($output) > 100) {
            return $output;
        }
        
        // Try without layout
        $output = @shell_exec("pdftotext -raw {$escapedPath} - 2>&1");
        
        return $output ?: '';
    }
    
    /**
     * ADVANCED PDF Stream Extraction (Pure PHP - No Dependencies)
     */
    private static function extractPDFStreams($filePath) {
        $content = file_get_contents($filePath);
        $text = '';
        
        // Extract all text objects between BT (Begin Text) and ET (End Text)
        $matches = [];
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
            foreach ($matches[1] as $textBlock) {
                // Method 1: Extract text within parentheses (most common)
                $textMatches = [];
                if (preg_match_all('/\(([^)]*)\)/s', $textBlock, $textMatches)) {
                    foreach ($textMatches[1] as $match) {
                        $decoded = self::decodePDFString($match);
                        if (!empty($decoded)) {
                            $text .= $decoded . ' ';
                        }
                    }
                }
                
                // Method 2: Extract text within brackets (array notation)
                $arrayMatches = [];
                if (preg_match_all('/\[(.*?)\]/s', $textBlock, $arrayMatches)) {
                    foreach ($arrayMatches[1] as $arrayContent) {
                        // Extract strings from array
                        $innerMatches = [];
                        if (preg_match_all('/\(([^)]*)\)/', $arrayContent, $innerMatches)) {
                            foreach ($innerMatches[1] as $match) {
                                $decoded = self::decodePDFString($match);
                                if (!empty($decoded)) {
                                    $text .= $decoded . ' ';
                                }
                            }
                        }
                    }
                }
                
                // Method 3: Tj and TJ operators (raw text)
                $tjMatches = [];
                if (preg_match_all('/\(([^)]+)\)\s*(?:Tj|TJ)/s', $textBlock, $tjMatches)) {
                    foreach ($tjMatches[1] as $match) {
                        $decoded = self::decodePDFString($match);
                        if (!empty($decoded)) {
                            $text .= $decoded . ' ';
                        }
                    }
                }
            }
        }
        
        // Also try to extract from compressed streams
        $text .= self::extractCompressedStreams($content);
        
        return $text;
    }
    
    /**
     * Decode PDF string (handles escape sequences)
     */
    private static function decodePDFString($str) {
        // Handle PDF escape sequences
        $replacements = [
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\b' => "\b",
            '\\f' => "\f",
            '\\(' => '(',
            '\\)' => ')',
            '\\\\' => '\\'
        ];
        
        $str = str_replace(array_keys($replacements), array_values($replacements), $str);
        
        // Handle octal sequences (\nnn)
        $str = preg_replace_callback('/\\\\(\d{1,3})/', function($matches) {
            return chr(octdec($matches[1]));
        }, $str);
        
        return $str;
    }
    
    /**
     * Extract text from compressed PDF streams
     */
    private static function extractCompressedStreams($content) {
        $text = '';
        
        // Find compressed streams (FlateDecode is most common)
        $matches = [];
        if (preg_match_all('/stream\s+(.*?)\s+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try to decompress with different methods
                $decompressed = false;
                
                // Method 1: gzuncompress
                $decompressed = @gzuncompress($stream);
                
                // Method 2: gzinflate
                if ($decompressed === false) {
                    $decompressed = @gzinflate($stream);
                }
                
                // Method 3: gzinflate with different window bits
                if ($decompressed === false) {
                    $decompressed = @gzinflate(substr($stream, 2));
                }
                
                if ($decompressed !== false) {
                    // Extract readable text from decompressed stream
                    $readable = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\xFF]/', ' ', $decompressed);
                    $words = [];
                    if (preg_match_all('/[a-zA-Z]{3,}/', $readable, $words)) {
                        $text .= implode(' ', $words[0]) . ' ';
                    }
                }
            }
        }
        
        return $text;
    }
    
    /**
     * ENHANCED DOCX Extraction
     */
    public static function extractFromDOCX($filePath) {
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive extension required for DOCX extraction");
        }
        
        $zip = new ZipArchive();
        $text = '';
        
        if ($zip->open($filePath) === true) {
            // Extract main document
            $xml = $zip->getFromName('word/document.xml');
            
            if ($xml) {
                $text = self::extractTextFromDocxXML($xml);
            }
            
            // Also extract from headers and footers
            for ($i = 1; $i <= 10; $i++) {
                $header = $zip->getFromName("word/header{$i}.xml");
                if ($header) {
                    $text .= "\n" . self::extractTextFromDocxXML($header);
                }
                
                $footer = $zip->getFromName("word/footer{$i}.xml");
                if ($footer) {
                    $text .= "\n" . self::extractTextFromDocxXML($footer);
                }
            }
            
            // Extract from footnotes if present
            $footnotes = $zip->getFromName('word/footnotes.xml');
            if ($footnotes) {
                $text .= "\n" . self::extractTextFromDocxXML($footnotes);
            }
            
            // Extract from endnotes if present
            $endnotes = $zip->getFromName('word/endnotes.xml');
            if ($endnotes) {
                $text .= "\n" . self::extractTextFromDocxXML($endnotes);
            }
            
            $zip->close();
        } else {
            throw new Exception("Failed to open DOCX file");
        }
        
        if (strlen($text) < 50) {
            throw new Exception("DOCX extraction failed - no text found");
        }
        
        return self::cleanText($text);
    }
    
    /**
     * Extract text from DOCX XML with proper formatting
     */
    private static function extractTextFromDocxXML($xml) {
        // Replace paragraph breaks with newlines
        $xml = str_replace(['</w:p>', '</w:br>'], "\n\n", $xml);
        
        // Replace text runs with spaces
        $xml = str_replace('</w:t>', ' ', $xml);
        
        // Replace tabs
        $xml = str_replace('<w:tab/>', "\t", $xml);
        
        // Remove all XML tags
        $text = strip_tags($xml);
        
        // Decode XML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        
        return $text;
    }
    
    /**
     * DOC (legacy) extraction
     */
    private static function extractFromDOC($filePath) {
        // Method 1: Try antiword if available
        if (!stristr(PHP_OS, 'WIN')) {
            $output = @shell_exec('which antiword 2>&1');
            if (!empty($output) && !stripos($output, 'not found')) {
                $escapedPath = escapeshellarg($filePath);
                $text = @shell_exec("antiword {$escapedPath} 2>&1");
                if (!empty($text) && strlen($text) > 100) {
                    return self::cleanText($text);
                }
            }
        }
        
        // Method 2: Binary extraction (basic)
        $content = file_get_contents($filePath);
        
        // DOC files have a specific structure - try to extract text
        $text = '';
        
        // Remove null bytes and control characters
        $content = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', ' ', $content);
        
        // Extract sequences of readable characters
        $matches = [];
        if (preg_match_all('/[a-zA-Z]{3,}(?:\s+[a-zA-Z]{3,}){2,}/u', $content, $matches)) {
            $text = implode(' ', $matches[0]);
        }
        
        if (strlen($text) < 100) {
            return "Legacy DOC format detected. Please convert to DOCX or PDF for optimal analysis. " .
                   "Text extraction from .DOC files is limited.";
        }
        
        return self::cleanText($text);
    }
    
    /**
     * Binary text extraction (fallback method)
     */
    private static function extractBinaryText($filePath) {
        $content = file_get_contents($filePath);
        
        // Remove binary junk, keep ASCII printable characters
        $content = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\xFF]/', ' ', $content);
        
        // Extract words (sequences of 3+ letters with optional punctuation)
        $matches = [];
        if (preg_match_all('/[a-zA-Z]{3,}(?:[.,;:!?\'\-\s]+[a-zA-Z]{3,})*/u', $content, $matches)) {
            return implode(' ', $matches[0]);
        }
        
        return '';
    }
    
    /**
     * Validate extracted text quality
     */
    private static function isValidText($text) {
        // Check if text has reasonable ratio of letters to other characters
        $matches = [];
        $letters = preg_match_all('/[a-zA-Z]/', $text, $matches);
        $total = strlen($text);
        
        if ($total === 0) return false;
        
        $ratio = $letters / $total;
        
        // Text should be at least 40% letters
        return $ratio > 0.4;
    }
    
    /**
     * ADVANCED Text Cleaning
     */
    private static function cleanText($text) {
        // Stage 1: Decode escape sequences
        $replacements = [
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\(' => '(',
            '\\)' => ')',
            '\\\\' => '\\'
        ];
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        // Stage 2: Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Stage 3: Remove excessive whitespace (but preserve paragraphs)
        $text = preg_replace('/[ \t]+/', ' ', $text); // Multiple spaces to single
        $text = preg_replace('/\n{4,}/', "\n\n\n", $text); // Limit consecutive newlines to 3
        
        // Stage 4: Remove control characters (except newlines and tabs)
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Stage 5: Decode HTML/XML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        
        // Stage 6: Fix common PDF extraction artifacts
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text); // Add space between camelCase
        $text = str_replace(['ﬁ', 'ﬂ'], ['fi', 'fl'], $text); // Fix ligatures
        
        // Stage 7: Remove page numbers and headers/footers patterns
        $text = preg_replace('/^\s*\d+\s*$/m', '', $text); // Standalone page numbers
        $text = preg_replace('/^Page \d+ of \d+\s*$/mi', '', $text); // "Page X of Y"
        
        // Stage 8: Normalize spacing around punctuation
        $text = preg_replace('/\s+([.,;:!?])/', '$1', $text); // Remove space before punctuation
        $text = preg_replace('/([.,;:!?])([a-zA-Z])/', '$1 $2', $text); // Add space after punctuation
        
        // Stage 9: Fix bullet points and lists
        $text = preg_replace('/^[•·∙▪▸►‣⁃-]\s*/m', '• ', $text);
        
        // Stage 10: Remove empty lines and trim
        $lines = array_filter(array_map('trim', explode("\n", $text)), function($line) {
            return strlen($line) > 0;
        });
        
        $text = implode("\n", $lines);
        
        // Stage 11: Ensure UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        return trim($text);
    }
    
    /**
     * Get extraction statistics (useful for debugging)
     */
    public static function getExtractionStats($filePath) {
        try {
            $text = self::extract($filePath);
            
            return [
                'success' => true,
                'word_count' => str_word_count($text),
                'char_count' => strlen($text),
                'line_count' => substr_count($text, "\n") + 1,
                'file_size' => filesize($filePath),
                'file_type' => pathinfo($filePath, PATHINFO_EXTENSION),
                'extraction_time' => 0 // Can be tracked if needed
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>