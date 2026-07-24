<?php
/**
 * Document Preview Handler
 * Provides preview for documents without forcing download
 * For DOCX/DOC files, uses Microsoft Office 365 Online Viewer
 * For PDF files, embeds directly in browser
 */

// Get the file path from query parameter
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Sanitize the file path to prevent directory traversal attacks
$file = basename($file);
$basePath = __DIR__ . '/pdf/policy/';
$filePath = $basePath . $file;

// Security checks
if (empty($file)) {
    http_response_code(400);
    echo "No file specified";
    exit;
}

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "File not found: " . htmlspecialchars($file);
    exit;
}

if (!is_file($filePath)) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

// Get file extension
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

// For PDF files, stream directly
if ($ext === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=3600');
    header('Pragma: public');
    readfile($filePath);
    exit;
}

// For Office documents (DOCX, DOC, XLS, XLSX, PPT, PPTX), use Office 365 Online Viewer
if (in_array($ext, array('docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt'))) {
    $fileUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', substr($filePath, strlen($_SERVER['DOCUMENT_ROOT'])));
    $viewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($fileUrl);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($file); ?></title>
        <style>
            * { margin: 0; padding: 0; }
            body { font-family: Arial, sans-serif; background: #f0f0f0; }
            .header {
                background: #2c3e50;
                color: white;
                padding: 15px 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .header h1 {
                font-size: 18px;
                font-weight: normal;
                margin-bottom: 5px;
            }
            .header p {
                font-size: 12px;
                opacity: 0.9;
            }
            .container {
                display: flex;
                flex-direction: column;
                height: 100vh;
            }
            .preview-container {
                flex: 1;
                overflow: hidden;
            }
            iframe {
                width: 100%;
                height: 100%;
                border: none;
            }
            .footer {
                background: #ecf0f1;
                padding: 10px 20px;
                font-size: 12px;
                color: #7f8c8d;
                border-top: 1px solid #bdc3c7;
            }
            .download-link {
                color: #18bc9c;
                text-decoration: none;
            }
            .download-link:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><?php echo htmlspecialchars($file); ?></h1>
                <p>Preview of the document</p>
            </div>
            <div class="preview-container">
                <iframe src="<?php echo htmlspecialchars($viewerUrl); ?>" allowfullscreen></iframe>
            </div>
            <div class="footer">
                <p>Powered by Microsoft Office 365 | 
                <a class="download-link" href="<?php echo htmlspecialchars($fileUrl); ?>" download>Download Document</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// For other file types, show a generic message
http_response_code(415);
echo "Unsupported file type: " . htmlspecialchars($ext);
exit;
?>
