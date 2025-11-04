<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class EnvService
{
    protected $envPath;

    public function __construct()
    {
        // Get the .env file path - check if it's in root or backend directory
        $rootPath = base_path();
        $backendPath = base_path('backend');
        
        if (File::exists($rootPath . '/.env')) {
            $this->envPath = $rootPath . '/.env';
        } elseif (File::exists($backendPath . '/.env')) {
            $this->envPath = $backendPath . '/.env';
        } else {
            $this->envPath = base_path('.env');
        }
    }

    /**
     * Get all environment variables
     */
    public function getAll()
    {
        $env = [];
        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $env[$key] = $value;
            }
        }
        
        return $env;
    }

    /**
     * Get a specific environment variable
     */
    public function get($key, $default = null)
    {
        $all = $this->getAll();
        return $all[$key] ?? $default;
    }

    /**
     * Set environment variables
     */
    public function set(array $variables)
    {
        if (!File::exists($this->envPath)) {
            throw new \Exception('.env file not found');
        }

        $env = $this->getAll();
        
        // Update values
        foreach ($variables as $key => $value) {
            $env[$key] = $value;
        }

        // Write back to file
        $content = '';
        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES);
        $keysWritten = [];
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Keep comments and empty lines as is
            if (strpos($trimmedLine, '#') === 0 || empty($trimmedLine)) {
                $content .= $line . "\n";
                continue;
            }
            
            // Parse and update existing keys
            if (strpos($line, '=') !== false) {
                list($key) = explode('=', $line, 2);
                $key = trim($key);
                
                if (isset($env[$key])) {
                    $value = $env[$key];
                    // Add quotes if value contains spaces or special characters
                    if (preg_match('/[\s=#]/', $value) || empty($value)) {
                        $value = '"' . addslashes($value) . '"';
                    }
                    $content .= $key . '=' . $value . "\n";
                    $keysWritten[] = $key;
                    unset($env[$key]);
                } else {
                    $content .= $line . "\n";
                }
            } else {
                $content .= $line . "\n";
            }
        }
        
        // Add new keys that weren't in the file
        foreach ($env as $key => $value) {
            if (!in_array($key, $keysWritten)) {
                // Add quotes if value contains spaces or special characters
                if (preg_match('/[\s=#]/', $value) || empty($value)) {
                    $value = '"' . addslashes($value) . '"';
                }
                $content .= $key . '=' . $value . "\n";
            }
        }
        
        File::put($this->envPath, $content);
        
        // Clear config cache
        \Artisan::call('config:clear');
    }
}


