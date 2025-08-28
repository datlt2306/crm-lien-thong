<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleHtmlspecialcharsError
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Wrap the response in a try-catch to handle htmlspecialchars errors
        try {
            $response = $next($request);
            
            // If it's a view response, ensure all data is properly formatted
            if ($response instanceof \Illuminate\View\View) {
                $data = $response->getData();
                
                // Recursively convert arrays to ensure they're properly formatted
                $this->sanitizeViewData($data);
                
                $response->with($data);
            }
            
            return $response;
        } catch (\ErrorException $e) {
            // Handle htmlspecialchars error specifically
            if (strpos($e->getMessage(), 'htmlspecialchars(): Argument #1 ($string) must be of type string, array given') !== false) {
                Log::error('Htmlspecialchars error detected', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                ]);
                
                // Return a generic error page
                return response()->view('errors.generic', [
                    'message' => 'Có lỗi xảy ra khi hiển thị trang. Vui lòng thử lại sau.',
                ], 500);
            }
            
            throw $e;
        }
    }
    
    /**
     * Recursively sanitize view data to ensure arrays are properly converted
     */
    private function sanitizeViewData(&$data): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $this->sanitizeViewData($value);
                } elseif (is_object($value) && method_exists($value, 'toArray')) {
                    $data[$key] = $value->toArray();
                } elseif (is_null($value)) {
                    $data[$key] = '';
                }
            }
        }
    }
}
