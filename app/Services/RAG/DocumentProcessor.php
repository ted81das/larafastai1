<?php

namespace App\Services\RAG;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DocumentProcessor
{
    protected array $supportedMimeTypes;
    
    public function __construct()
    {
        $this->supportedMimeTypes = [
            'application/pdf' => 'extractPdf',
            'text/plain' => 'extractText',
            'text/markdown' => 'extractText',
            'text/csv' => 'extractText',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'extractDocx',
            'application/msword' => 'extractDoc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'extractPptx',
            'application/vnd.ms-powerpoint' => 'extractPpt',
            'text/html' => 'extractHtml',
        ];
    }
    
    /**
     * Extract text from a document
     *
     * @param string $filePath Path to the document
     * @param string|null $mimeType Mime type of the document
     * @return string Extracted text
     */
    public function extractText(string $filePath, ?string $mimeType = null): string
    {
        // Detect mime type if not provided
        if ($mimeType === null) {
            $mimeType = mime_content_type($filePath);
        }
        
        // Find the appropriate extraction method
        $extractionMethod = $this->supportedMimeTypes[$mimeType] ?? null;
        
        if ($extractionMethod && method_exists($this, $extractionMethod)) {
            return $this->$extractionMethod($filePath);
        }
        
        // Default to treating as plain text
        return $this->extractPlainText($filePath);
    }
    
    /**
     * Extract text from a plain text file
     *
     * @param string $filePath Path to the file
     * @return string Extracted text
     */
    protected function extractPlainText(string $filePath): string
    {
        try {
            return file_get_contents($filePath);
        } catch (\Exception $e) {
            Log::error('Error extracting text from plain text file', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return '';
        }
    }
    
    /**
     * Extract text from a PDF file
     *
     * @param string $filePath Path to the PDF file
     * @return string Extracted text
     */
    protected function extractPdf(string $filePath): string
    {
        try {
            // Check if pdftotext is available
            $process = new Process(['which', 'pdftotext']);
            $process->run();
            
            if ($process->isSuccessful()) {
                // Use pdftotext to extract text
                $outputFile = tempnam(sys_get_temp_dir(), 'pdf_');
                $process = new Process(['pdftotext', $filePath, $outputFile]);
                $process->setTimeout(60);
                $process->run();
                
                if ($process->isSuccessful()) {
                    $text = file_get_contents($outputFile);
                    unlink($outputFile);
                    return $text;
                }
            }
            
            // Fallback to PHP library
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::error('Error extracting text from PDF', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return '';
        }
    }
    
    /**
     * Extract text from a DOCX file
     *
     * @param string $filePath Path to the DOCX file
     * @return string Extracted text
     */
    protected function extractDocx(string $filePath): string
    {
        try {
            $content = '';
            
            $zip = new \ZipArchive();
            if ($zip->open($filePath)) {
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $data = $zip->getFromIndex($index);
                    $zip->close();
                    
                    $content = $this->stripDocxTags($data);
                }
            }
            
            return $content;
        } catch (\Exception $e) {
            Log::error('Error extracting text from DOCX', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return '';
        }
    }
    
    /**
     * Strip XML tags from DOCX content
     *
     * @param string $data XML data
     * @return string Cleaned text
     */
    protected function stripDocxTags(string $data): string
    {
        $content = '';
        
        $dom = new \DOMDocument();
        $dom->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        
        $paragraphs = $dom->getElementsByTagName('p');
        foreach ($paragraphs as $paragraph) {
            $content .= $paragraph->textContent . "\n";
        }
        
        return trim($content);
    }
    
    /**
     * Extract text from an HTML file
     *
     * @param string $filePath Path to the HTML file
     * @return string Extracted text
     */
    protected function extractHtml(string $filePath): string
    {
        try {
            $html = file_get_contents($filePath);
            
            // Remove scripts and styles
            $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
            $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
            
            // Use DOMDocument to extract text
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            
            $text = $dom->textContent;
            
            // Clean up whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            return $text;
        } catch (\Exception $e) {
            Log::error('Error extracting text from HTML', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return '';
        }
    }
    
    /**
     * Extract metadata from a document
     *
     * @param string $filePath Path to the document
     * @param string|null $mimeType Mime type of the document
     * @return array Metadata
     */
    public function extractMetadata(string $filePath, ?string $mimeType = null): array
    {
        // Detect mime type if not provided
        if ($mimeType === null) {
            $mimeType = mime_content_type($filePath);
        }
        
        $metadata = [
            'mime_type' => $mimeType,
            'file_size' => filesize($filePath),
            'last_modified' => filemtime($filePath),
        ];
        
        // Add more metadata extraction based on file type
        if ($mimeType === 'application/pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $details = $pdf->getDetails();
                
                // Extract common PDF metadata
                $fields = ['Author', 'CreationDate', 'Creator', 'Keywords', 'ModDate', 'Producer', 'Subject', 'Title'];
                
                foreach ($fields as $field) {
                    if (isset($details[$field])) {
                        $metadata[strtolower($field)] = $details[$field];
                    }
                }
                
                // Add page count
                $metadata['page_count'] = count($pdf->getPages());
            } catch (\Exception $e) {
                Log::warning('Error extracting PDF metadata', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $metadata;
    }
    
    /**
     * Split text into chunks for processing
     *
     * @param string $text Text to split
     * @param int $chunkSize Maximum characters per chunk
     * @param int $overlap Overlap between chunks
     * @return array Chunks with metadata
     */
    public function splitIntoChunks(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        if (empty($text)) {
            return [];
        }
        
        // Clean text - remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        $chunks = [];
        $sentences = $this->splitIntoSentences($text);
        
        $currentChunk = '';
        $currentPosition = 0;
        
        foreach ($sentences as $sentence) {
            // If adding this sentence would exceed chunk size and we already have content
            if (strlen($currentChunk) + strlen($sentence) > $chunkSize && !empty($currentChunk))
            // If adding this sentence would exceed chunk size and we already have content
            if (strlen($currentChunk) + strlen($sentence) > $chunkSize && !empty($currentChunk)) {
                // Save current chunk
                $chunks[] = [
                    'text' => $currentChunk,
                    'start_position' => $currentPosition,
                    'end_position' => $currentPosition + strlen($currentChunk),
                ];
                
                // Start a new chunk with overlap
                $overlapStart = max(0, strlen($currentChunk) - $overlap);
                $currentChunk = substr($currentChunk, $overlapStart);
                $currentPosition += $overlapStart;
            }
            
            $currentChunk .= $sentence . ' ';
        }
        
        // Add the final chunk if not empty
        if (!empty($currentChunk)) {
            $chunks[] = [
                'text' => trim($currentChunk),
                'start_position' => $currentPosition,
                'end_position' => $currentPosition + strlen($currentChunk),
            ];
        }
        
        return $chunks;
    }
    
    /**
     * Split text into sentences
     *
     * @param string $text Text to split
     * @return array Sentences
     */
    protected function splitIntoSentences(string $text): array
    {
        // Split on sentence endings (., !, ?) followed by a space or newline
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Handle cases where there are no proper sentence endings
        if (count($sentences) <= 1 && strlen($text) > 1000) {
            // Fall back to splitting by paragraphs or newlines
            $sentences = preg_split('/\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            
            // If still too long, split by periods even without spaces
            if (count($sentences) <= 1 && strlen($text) > 1000) {
                $sentences = preg_split('/\./', $text, -1, PREG_SPLIT_NO_EMPTY);
                
                // Add periods back since they were removed by the split
                $sentences = array_map(function($s) { return trim($s) . '.'; }, $sentences);
            }
        }
        
        return $sentences;
    }
    
    /**
     * Check if a document type is supported
     *
     * @param string $mimeType MIME type to check
     * @return bool Whether the type is supported
     */
    public function isSupportedType(string $mimeType): bool
    {
        return isset($this->supportedMimeTypes[$mimeType]);
    }
    
    /**
     * Get list of supported MIME types
     *
     * @return array Supported MIME types
     */
    public function getSupportedTypes(): array
    {
        return array_keys($this->supportedMimeTypes);
    }
}
