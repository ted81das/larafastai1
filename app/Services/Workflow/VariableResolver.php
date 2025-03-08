<?php 
namespace App\Services\Workflow;

class VariableResolver
{
    /**
     * Resolve variables in node configuration
     */
    public function resolveNodeInput(array $config, ?array $inputMapping, array $input): array
    {
        // If input mapping is defined, map input values
        if ($inputMapping) {
            $mappedInput = [];
            
            foreach ($inputMapping as $source => $target) {
                $value = $this->getValueByPath($input, $source);
                $mappedInput = $this->setValueByPath($mappedInput, $target, $value);
            }
            
            // Use mapped input for variable resolution
            $resolvedConfig = $this->resolveVariables($config, $mappedInput);
        } else {
            // Use original input for variable resolution
            $resolvedConfig = $this->resolveVariables($config, $input);
        }
        
        return $resolvedConfig;
    }
    
    /**
     * Resolve variables in an array recursively
     */
    public function resolveVariables(array $data, array $variables): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->resolveVariables($value, $variables);
            } elseif (is_string($value)) {
                $result[$key] = $this->resolveVariablesInString($value, $variables);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Resolve variables in a string
     */
    protected function resolveVariablesInString(string $str, array $variables): string
    {
        // Replace {{variable}} pattern
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($variables) {
            $path = trim($matches[1]);
            $value = $this->getValueByPath($variables, $path);
            
            // Convert arrays and objects to JSON
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }
            
            // Return string value or empty string for null
            return is_null($value) ? '' : (string) $value;
        }, $str);
    }
    
    /**
     * Get value from nested array by dot notation path
     */
    public function getValueByPath(array $data, string $path)
    {
        // Handle empty path
        if (empty($path)) {
            return null;
        }
        
        // Split path into segments
        $segments = explode('.', $path);
        $current = $data;
        
        // Navigate through path segments
        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif (is_object($current) && property_exists($current, $segment)) {
                $current = $current->{$segment};
            } else {
                return null; // Path segment not found
            }
        }
        
        return $current;
    }
    
    /**
     * Set value in nested array by dot notation path
     */
    public function setValueByPath(array $data, string $path, $value): array
    {
        // Handle empty path
        if (empty($path)) {
            return $data;
        }
        
        // Split path into segments
        $segments = explode('.', $path);
        $current = &$data;
        
        // Navigate through path segments and create if not exists
        foreach ($segments as $i => $segment) {
            // If this is the last segment, set the value
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
            } else {
                // Create the path if it doesn't exist
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                
                // Move reference to next level
                $current = &$current[$segment];
            }
        }
        
        return $data;
    }
}
