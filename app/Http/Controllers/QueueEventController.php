<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QueueEventController extends Controller
{
    /**
     * Stream queue events using Server-Sent Events (SSE).
     */
    public function stream(Request $request)
    {
        $response = new StreamedResponse(function () use ($request) {
            // Keep connection alive indefinitely
            set_time_limit(0);
            
            // Client can pass Last-Event-ID if they reconnect
            // In SSE, the Last-Event-ID is a string. We're using microtime string.
            $lastEventId = $request->header('Last-Event-ID');
            
            // If they didn't send a header, just use the current time so we don't dump old events on load
            if (!$lastEventId) {
                $lastEventId = microtime(true);
            }

            while (true) {
                // If client disconnects, break the loop
                if (connection_aborted()) {
                    break;
                }

                $events = Cache::get('recent_queue_events', []);
                
                $sentAny = false;
                foreach ($events as $event) {
                    if ($event['timestamp'] > (float)$lastEventId) {
                        $this->sendSSE($event['timestamp'], $event['event'], $event['data']);
                        $lastEventId = $event['timestamp'];
                        $sentAny = true;
                    }
                }

                // Send a keep-alive ping every 15 seconds to prevent connection timeout
                static $lastPing = 0;
                if (time() - $lastPing > 15 && !$sentAny) {
                    echo ": ping\n\n";
                    ob_flush();
                    flush();
                    $lastPing = time();
                }

                // Sleep to prevent tight loop and high CPU usage
                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // Important for Nginx

        return $response;
    }

    private function sendSSE($id, $event, $data)
    {
        echo "id: {$id}\n";
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        ob_flush();
        flush();
    }
}
