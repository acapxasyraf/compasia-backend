<?php

use Illuminate\Contracts\Console\Kernel;
use Laravel\Sanctum\PersonalAccessToken;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$server = stream_socket_server('tcp://0.0.0.0:8081', $errno, $errstr);
if (!$server) {
    fwrite(STDERR, "WebSocket server error: {$errstr}\n");
    exit(1);
}

stream_set_blocking($server, false);

$clients = [];
$sentIds = [];

$getMessages = function (): array {
    $file = storage_path('app/ws_messages.json');
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }
    $content = file_get_contents($file);
    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
};

$sendFrame = function ($client, string $payload): void {
    $length = strlen($payload);
    $header = chr(129);
    if ($length <= 125) {
        $header .= chr($length);
    } elseif ($length <= 65535) {
        $header .= chr(126).pack('n', $length);
    } else {
        $header .= chr(127).pack('J', $length);
    }
    fwrite($client, $header.$payload);
};

$parseToken = function (string $request): ?string {
    $lines = preg_split('/\r\n/', $request);
    $requestLine = $lines[0] ?? '';
    if (!$requestLine) {
        return null;
    }
    $parts = explode(' ', $requestLine);
    if (count($parts) < 2) {
        return null;
    }
    $path = $parts[1];
    $query = parse_url($path, PHP_URL_QUERY);
    parse_str($query ?? '', $params);
    return $params['token'] ?? null;
};

while (true) {
    $read = [$server];
    foreach ($clients as $client) {
        $read[] = $client['stream'];
    }
    $write = null;
    $except = null;
    stream_select($read, $write, $except, 0, 200000);

    if (in_array($server, $read, true)) {
        $client = stream_socket_accept($server, 0);
        if ($client) {
            stream_set_blocking($client, true);
            $request = fread($client, 2048);
            $token = $parseToken($request);
            $tokenModel = $token ? PersonalAccessToken::findToken($token) : null;

            if (!$tokenModel) {
                fwrite($client, "HTTP/1.1 401 Unauthorized\r\n\r\n");
                fclose($client);
            } else {
                preg_match('#Sec-WebSocket-Key:\s*(.*)$#m', $request, $matches);
                $key = trim($matches[1] ?? '');
                if (!$key) {
                    fclose($client);
                } else {
                    $accept = base64_encode(pack('H*', sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
                    $response = "HTTP/1.1 101 Switching Protocols\r\n".
                        "Upgrade: websocket\r\n".
                        "Connection: Upgrade\r\n".
                        "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
                    fwrite($client, $response);
                    stream_set_blocking($client, false);
                    $clients[(int) $client] = [
                        'stream' => $client,
                        'user_id' => $tokenModel->tokenable_id,
                    ];
                }
            }
        }
    }

    foreach ($clients as $id => $client) {
        if (in_array($client['stream'], $read, true)) {
            $data = fread($client['stream'], 2048);
            if ($data === '' || $data === false) {
                fclose($client['stream']);
                unset($clients[$id]);
            }
        }
    }

    $messages = $getMessages();
    foreach ($messages as $message) {
        $messageId = $message['id'] ?? null;
        if (!$messageId || isset($sentIds[$messageId])) {
            continue;
        }
        $payload = json_encode($message['payload'] ?? []);
        foreach ($clients as $client) {
            $sendFrame($client['stream'], $payload ?: '{}');
        }
        $sentIds[$messageId] = true;
    }

    if (count($sentIds) > 200) {
        $sentIds = array_slice($sentIds, -100, null, true);
    }
}
