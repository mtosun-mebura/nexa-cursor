<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DemoController extends Controller
{
    /**
     * Show demo layout pages (demo1-demo10)
     */
    public function show($demoNumber)
    {
        $validDemos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        
        if (!in_array($demoNumber, $validDemos)) {
            abort(404, 'Demo not found');
        }

        // Use Blade views from metronic-tailwind-laravel integration
        $viewPath = "pages.demo{$demoNumber}.index";
        
        if (view()->exists($viewPath)) {
            return view($viewPath, ['demoNumber' => $demoNumber]);
        }

        // First, try public/html/demo{$demoNumber}/index.html
        $publicPath = public_path("html/demo{$demoNumber}/index.html");
        
        // Fallback: Try to load Metronic HTML file (old method)
        $metronicPath = base_path("../metronic-v9.3.4/metronic-tailwind-html-demos/dist/html/demo{$demoNumber}/index.html");
        
        // Determine which path to use
        $htmlPath = null;
        if (File::exists($publicPath)) {
            $htmlPath = $publicPath;
        } elseif (File::exists($metronicPath)) {
            $htmlPath = $metronicPath;
        }
        
        if ($htmlPath && File::exists($htmlPath)) {
            $html = File::get($htmlPath);
            
            // Apply same transformations as subpages
            $html = preg_replace('/<base href="[^"]*">/', '<base href="/">', $html);
            
            // Replace relative asset paths to absolute paths (multiple patterns)
            // Handle src and href attributes with double quotes
            $html = preg_replace('/(src|href)="assets\//', '$1="/assets/', $html);
            // Handle src and href attributes with single quotes
            $html = preg_replace('/(src|href)=\'assets\//', '$1=\'/assets/', $html);
            // Handle src and href without quotes (less common but possible)
            $html = preg_replace('/(src|href)=assets\//', '$1="/assets/', $html);
            
            // Also handle data attributes and other patterns
            $html = preg_replace('/data-kt-[^=]*="assets\//', 'data-kt-$1="/assets/', $html);
            $html = preg_replace('/data-kt-[^=]*=\'assets\//', 'data-kt-$1=\'/assets/', $html);
            
            // Fix any remaining relative paths in CSS/JS (url() functions)
            $html = str_replace('url(assets/', 'url(/assets/', $html);
            $html = str_replace('url("assets/', 'url("/assets/', $html);
            $html = str_replace("url('assets/", "url('/assets/", $html);
            $html = str_replace('url(../../assets/', 'url(/assets/', $html);
            $html = str_replace('url(../../../assets/', 'url(/assets/', $html);
            $html = str_replace('url(../../../../assets/', 'url(/assets/', $html);
            
            // Fix demo links - convert html/demoX/... to /demoX/...
            $html = preg_replace('/href="html\/demo(\d+)\.html"/', 'href="/demo$1"', $html);
            $html = preg_replace('/href="html\/demo(\d+)\/([^"]+)"/', 'href="/demo$1/$2"', $html);
            $html = preg_replace('/href=\'html\/demo(\d+)\.html\'/', 'href=\'/demo$1\'', $html);
            $html = preg_replace('/href=\'html\/demo(\d+)\/([^\']+)\'/', 'href=\'/demo$1/$2\'', $html);
            
            // Fix any remaining html/ paths (catch-all)
            $html = preg_replace('/href="html\/([^"]+)"/', 'href="/demo' . $demoNumber . '/$1"', $html);
            $html = preg_replace('/href=\'html\/([^\']+)\'/', 'href=\'/demo' . $demoNumber . '/$1\'', $html);
            
            // Fix relative paths (multiple levels)
            $html = str_replace('../assets/', '/assets/', $html);
            $html = str_replace('../../assets/', '/assets/', $html);
            $html = str_replace('../../../assets/', '/assets/', $html);
            $html = str_replace('../../../../assets/', '/assets/', $html);
            $html = str_replace('../../../../../assets/', '/assets/', $html);
            
            // Fix meta tags (content attribute)
            $html = preg_replace('/content="assets\//', 'content="/assets/', $html);
            $html = preg_replace('/content=\'assets\//', 'content=\'/assets/', $html);
            
            // Fix script src paths - replace all script src that start with assets/
            $html = preg_replace_callback('/<script([^>]*)\ssrc="(assets\/[^"]*)"/', function($matches) {
                return '<script' . $matches[1] . ' src="/' . $matches[2] . '"';
            }, $html);
            $html = preg_replace_callback('/<script([^>]*)\ssrc=\'(assets\/[^\']*)\'/', function($matches) {
                return '<script' . $matches[1] . ' src=\'/' . $matches[2] . '\'';
            }, $html);
            
            // Fix link href for stylesheets and other links
            $html = preg_replace_callback('/<link([^>]*)\shref="(assets\/[^"]*)"/', function($matches) {
                return '<link' . $matches[1] . ' href="/' . $matches[2] . '"';
            }, $html);
            $html = preg_replace_callback('/<link([^>]*)\shref=\'(assets\/[^\']*)\'/', function($matches) {
                return '<link' . $matches[1] . ' href=\'/' . $matches[2] . '\'';
            }, $html);
            
            // Fix img src paths
            $html = preg_replace_callback('/<img([^>]*)\ssrc="(assets\/[^"]*)"/', function($matches) {
                return '<img' . $matches[1] . ' src="/' . $matches[2] . '"';
            }, $html);
            $html = preg_replace_callback('/<img([^>]*)\ssrc=\'(assets\/[^\']*)\'/', function($matches) {
                return '<img' . $matches[1] . ' src=\'/' . $matches[2] . '\'';
            }, $html);
            
            // Fix source src paths (for <source> tags)
            $html = preg_replace_callback('/<source([^>]*)\ssrc="(assets\/[^"]*)"/', function($matches) {
                return '<source' . $matches[1] . ' src="/' . $matches[2] . '"';
            }, $html);
            
            // Fix background-image in style attributes
            $html = preg_replace_callback('/style="([^"]*background[^"]*url\([^)]*assets\/[^)]*\)[^"]*)"/', function($matches) {
                if (isset($matches[1])) {
                    return 'style="' . str_replace('assets/', '/assets/', $matches[1]) . '"';
                }
                return $matches[0];
            }, $html);
            
            // Fix canonical and og:url links
            $currentUrl = url("/demo{$demoNumber}");
            $html = preg_replace('/<link[^>]*rel="canonical"[^>]*>/', '', $html);
            $html = preg_replace('/<meta[^>]*property="og:url"[^>]*>/', '', $html);
            $canonicalTag = '<link href="' . $currentUrl . '" rel="canonical"/>';
            $ogUrlTag = '<meta content="' . $currentUrl . '" property="og:url"/>';
            $html = str_replace('</head>', $canonicalTag . "\n  " . $ogUrlTag . "\n </head>", $html);
            
            return response($html)->header('Content-Type', 'text/html');
        }

        // Final fallback
        abort(404, 'Demo not found');
    }

    /**
     * Show demo subpages (e.g., /demo1/dashboards/dark-sidebar)
     */
    public function showSubpage($demoNumber, $path = '')
    {
        try {
            $validDemos = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
            
            if (!in_array($demoNumber, $validDemos)) {
                abort(404, 'Demo not found');
            }

            // Normalize path - remove .html extension if present and clean up
            $path = trim($path, '/');
            $originalPath = $path;
            $path = str_replace('.html', '', $path);
            
            // Try to find the HTML file - multiple strategies
            $metronicPath = null;
            
            // First, try public/html/demo{$demoNumber}/ directory
            $publicBasePath = public_path("html/demo{$demoNumber}/");
            
            // Fallback to metronic-v9.3.4 directory
            $metronicBasePathRaw = base_path("../metronic-v9.3.4/metronic-tailwind-html-demos/dist/html/demo{$demoNumber}/");
            
            // Determine which base path to use
            $basePath = null;
            if (is_dir($publicBasePath)) {
                $basePath = $publicBasePath;
            } else {
                // Normalize the base path (resolve ..)
                $basePath = realpath($metronicBasePathRaw);
                if ($basePath === false) {
                    // If realpath fails, try without it (might be a symlink issue)
                    $basePath = rtrim($metronicBasePathRaw, '/') . '/';
                    if (!is_dir($basePath)) {
                        abort(500, 'Base path does not exist: ' . $metronicBasePathRaw);
                    }
                } else {
                    $basePath = $basePath . '/';
                }
            }
            
            // Strategy 1: Try as direct file (e.g., account/billing/basic.html)
            if ($path) {
                $htmlFile = $path . '.html';
                $testPath = rtrim($basePath, '/') . '/' . ltrim($htmlFile, '/');
                if (File::exists($testPath)) {
                    $metronicPath = $testPath;
                }
            }
            
            // Strategy 2: If file doesn't exist, try as directory with index.html
            if (!$metronicPath && $path) {
                $testPath = rtrim($basePath, '/') . '/' . trim($path, '/') . '/index.html';
                if (File::exists($testPath)) {
                    $metronicPath = $testPath;
                }
            }
            
            // Strategy 3: Try with .html extension in subdirectories (e.g., reset-password/change-password.html)
            // This is the same as Strategy 1, but let's keep it for clarity
            if (!$metronicPath && $path) {
                $parts = explode('/', $path);
                if (count($parts) > 1) {
                    $lastPart = array_pop($parts);
                    $dirPath = implode('/', $parts);
                    $testPath = rtrim($basePath, '/') . '/' . trim($dirPath, '/') . '/' . $lastPart . '.html';
                    if (File::exists($testPath)) {
                        $metronicPath = $testPath;
                    }
                }
            }
            
            // Strategy 4: Try nested index.html (e.g., reset-password/change-password/index.html)
            if (!$metronicPath && $path) {
                $parts = explode('/', $path);
                if (count($parts) > 1) {
                    $lastPart = array_pop($parts);
                    $dirPath = implode('/', $parts);
                    $testPath = rtrim($basePath, '/') . '/' . trim($dirPath, '/') . '/' . $lastPart . '/index.html';
                    if (File::exists($testPath)) {
                        $metronicPath = $testPath;
                    }
                }
            }
            
            if ($metronicPath && File::exists($metronicPath)) {
                try {
                    $html = File::get($metronicPath);
                } catch (\Exception $e) {
                    abort(500, 'Error reading file: ' . $e->getMessage());
                }
                    
                    // Apply same transformations as main demo page
                    $html = preg_replace('/<base href="[^"]*">/', '<base href="/">', $html);
                
                // Replace relative asset paths to absolute paths (multiple patterns)
                // Handle src and href attributes with double quotes
                $html = preg_replace('/(src|href)="assets\//', '$1="/assets/', $html);
                // Handle src and href attributes with single quotes
                $html = preg_replace('/(src|href)=\'assets\//', '$1=\'/assets/', $html);
                // Handle src and href without quotes (less common but possible)
                $html = preg_replace('/(src|href)=assets\//', '$1="/assets/', $html);
                
                // Also handle data attributes and other patterns
                $html = preg_replace('/data-kt-[^=]*="assets\//', 'data-kt-$1="/assets/', $html);
                $html = preg_replace('/data-kt-[^=]*=\'assets\//', 'data-kt-$1=\'/assets/', $html);
                
                // Fix any remaining relative paths in CSS/JS (url() functions)
                $html = str_replace('url(assets/', 'url(/assets/', $html);
                $html = str_replace('url("assets/', 'url("/assets/', $html);
                $html = str_replace("url('assets/", "url('/assets/", $html);
                $html = str_replace('url(../../assets/', 'url(/assets/', $html);
                $html = str_replace('url(../../../assets/', 'url(/assets/', $html);
                $html = str_replace('url(../../../../assets/', 'url(/assets/', $html);
                
                // Fix demo links - convert html/demoX/... to /demoX/...
                $html = preg_replace('/href="html\/demo(\d+)\.html"/', 'href="/demo$1"', $html);
                $html = preg_replace('/href="html\/demo(\d+)\/([^"]+)"/', 'href="/demo$1/$2"', $html);
                $html = preg_replace('/href=\'html\/demo(\d+)\.html\'/', 'href=\'/demo$1\'', $html);
                $html = preg_replace('/href=\'html\/demo(\d+)\/([^\']+)\'/', 'href=\'/demo$1/$2\'', $html);
                
                // Fix any remaining html/ paths (catch-all)
                $html = preg_replace('/href="html\/([^"]+)"/', 'href="/demo' . $demoNumber . '/$1"', $html);
                $html = preg_replace('/href=\'html\/([^\']+)\'/', 'href=\'/demo' . $demoNumber . '/$1\'', $html);
                
                // Fix relative paths (multiple levels)
                $html = str_replace('../assets/', '/assets/', $html);
                $html = str_replace('../../assets/', '/assets/', $html);
                $html = str_replace('../../../assets/', '/assets/', $html);
                $html = str_replace('../../../../assets/', '/assets/', $html);
                $html = str_replace('../../../../../assets/', '/assets/', $html);
                
                // Fix meta tags (content attribute)
                $html = preg_replace('/content="assets\//', 'content="/assets/', $html);
                $html = preg_replace('/content=\'assets\//', 'content=\'/assets/', $html);
                
                // Fix script src paths - replace all script src that start with assets/
                $html = preg_replace_callback('/<script([^>]*)\ssrc="(assets\/[^"]*)"/', function($matches) {
                    return '<script' . $matches[1] . ' src="/' . $matches[2] . '"';
                }, $html);
                $html = preg_replace_callback('/<script([^>]*)\ssrc=\'(assets\/[^\']*)\'/', function($matches) {
                    return '<script' . $matches[1] . ' src=\'/' . $matches[2] . '\'';
                }, $html);
                
                // Fix link href for stylesheets and other links
                $html = preg_replace_callback('/<link([^>]*)\shref="(assets\/[^"]*)"/', function($matches) {
                    return '<link' . $matches[1] . ' href="/' . $matches[2] . '"';
                }, $html);
                $html = preg_replace_callback('/<link([^>]*)\shref=\'(assets\/[^\']*)\'/', function($matches) {
                    return '<link' . $matches[1] . ' href=\'/' . $matches[2] . '\'';
                }, $html);
                
                // Fix img src paths
                $html = preg_replace_callback('/<img([^>]*)\ssrc="(assets\/[^"]*)"/', function($matches) {
                    return '<img' . $matches[1] . ' src="/' . $matches[2] . '"';
                }, $html);
                $html = preg_replace_callback('/<img([^>]*)\ssrc=\'(assets\/[^\']*)\'/', function($matches) {
                    return '<img' . $matches[1] . ' src=\'/' . $matches[2] . '\'';
                }, $html);
                
                // Fix source src paths (for <source> tags)
                $html = preg_replace_callback('/<source([^>]*)\ssrc="(assets\/[^"]*)"/', function($matches) {
                    return '<source' . $matches[1] . ' src="/' . $matches[2] . '"';
                }, $html);
                
                // Fix background-image in style attributes
                $html = preg_replace_callback('/style="([^"]*background[^"]*url\([^)]*assets\/[^)]*\)[^"]*)"/', function($matches) {
                    if (isset($matches[1])) {
                        return 'style="' . str_replace('assets/', '/assets/', $matches[1]) . '"';
                    }
                    return $matches[0];
                }, $html);
                
                // Fix canonical and og:url links
                $currentUrl = url("/demo{$demoNumber}/" . $originalPath);
                $html = preg_replace('/<link[^>]*rel="canonical"[^>]*>/', '', $html);
                $html = preg_replace('/<meta[^>]*property="og:url"[^>]*>/', '', $html);
                $canonicalTag = '<link href="' . $currentUrl . '" rel="canonical"/>';
                $ogUrlTag = '<meta content="' . $currentUrl . '" property="og:url"/>';
                $html = str_replace('</head>', $canonicalTag . "\n  " . $ogUrlTag . "\n </head>", $html);
                
                // Ensure the correct demo layout JS is loaded
                // Check if demo layout JS script exists, if not add it before closing body tag
                if (strpos($html, 'assets/js/layouts/demo' . $demoNumber . '.js') === false) {
                    $demoScript = '<script src="/assets/js/layouts/demo' . $demoNumber . '.js"></script>';
                    // Insert before </body> or before other scripts
                    if (strpos($html, '</body>') !== false) {
                        $html = str_replace('</body>', $demoScript . "\n</body>", $html);
                    } else {
                        // If no </body> tag, append before last script
                        $html = preg_replace('/(<script[^>]*>.*?<\/script>)(\s*)$/s', '$1' . "\n" . $demoScript . '$2', $html);
                    }
                }
                
                return response($html)->header('Content-Type', 'text/html');
            }
            
            // If we get here, the file was not found
            // Build a helpful error message
            $triedPaths = [];
            if ($path) {
                $triedPaths[] = rtrim($basePath, '/') . '/' . ltrim($path, '/') . '.html';
                $triedPaths[] = rtrim($basePath, '/') . '/' . trim($path, '/') . '/index.html';
            }
            
            if ($path && strpos($path, '/') !== false) {
                $parts = explode('/', $path);
                $lastPart = array_pop($parts);
                $dirPath = implode('/', $parts);
                $triedPaths[] = rtrim($basePath, '/') . '/' . trim($dirPath, '/') . '/' . $lastPart . '.html';
                $triedPaths[] = rtrim($basePath, '/') . '/' . trim($dirPath, '/') . '/' . $lastPart . '/index.html';
            }
            
            $triedPathsStr = implode("\n", array_map(function($p) {
                return '  - ' . $p . (file_exists($p) ? ' (EXISTS)' : ' (NOT FOUND)');
            }, $triedPaths));
            
            abort(404, "Demo subpage not found: {$path}\nBase path: {$basePath}\nTried paths:\n{$triedPathsStr}");
        } catch (\Exception $e) {
            \Log::error('DemoController error: ' . $e->getMessage(), [
                'demoNumber' => $demoNumber ?? 'unknown',
                'path' => $path ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error loading demo page: ' . $e->getMessage());
        }
    }
}
