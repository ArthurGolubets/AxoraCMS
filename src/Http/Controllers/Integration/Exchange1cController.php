<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use HolartWeb\AxoraCMS\Models\Integration\TCommerceMLSetting;
use HolartWeb\AxoraCMS\Services\Integration\CommerceMLImportService;
use HolartWeb\AxoraCMS\Services\Integration\CommerceMLSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Exchange1cController extends Controller
{
    /**
     * Формирование ответа для 1С
     */
    private function answer(string $message): Response
    {
        return response($message, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Connection', 'close')
            ->header('Content-Length', strlen($message));
    }

    /**
     * Verify HTTP Basic credentials against the CommerceML settings.
     * The integration must also be enabled.
     */
    private function authenticate(Request $request): bool
    {
        $settings = TCommerceMLSetting::getSettings();

        if (! $settings->is_enabled) {
            return false;
        }

        $user = (string) $request->server('PHP_AUTH_USER', '');
        $pass = (string) $request->server('PHP_AUTH_PW', '');

        return $user !== ''
            && hash_equals((string) $settings->login, $user)
            && hash_equals((string) $settings->password, $pass);
    }

    /**
     * 401 challenge for the JSON/plain endpoints.
     */
    private function unauthorized(): Response
    {
        return response("failure\nНеверный логин или пароль", 401)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('WWW-Authenticate', 'Basic realm="1C"');
    }

    public function index(Request $request)
    {
        $type = $request->query('type', 'catalog');
        $mode = $request->query('mode');

        Log::info("[1C] [{$type}:{$mode}] Входящий запрос", [
            'query' => $request->query(),
            'method' => $request->method(),
            'content_length' => $request->header('Content-Length'),
            'content_type' => $request->header('Content-Type'),
            'ip' => $request->ip(),
        ]);

        // checkauth performs its own credential check and keeps the legacy
        // "failure\n..." body. Every other data mode requires HTTP Basic here.
        if (in_array($mode, ['file', 'import', 'deactivate', 'complete'], true) && ! $this->authenticate($request)) {
            Log::warning("[1C] [{$mode}] Отклонён неавторизованный запрос", ['ip' => $request->ip()]);

            return $this->unauthorized();
        }

        return match ($mode) {
            'checkauth' => $this->checkAuth($request),
            'init' => $this->init($request),
            'file' => $this->saveFile($request),
            'import' => $this->import($request),
            'deactivate' => $this->deactivate($request),
            'complete' => $this->complete($request),
            default => $this->answer("success\n"),
        };
    }

    private function saveFile(Request $request): Response
    {
        $filename = $request->query('filename', 'unknown');
        $content = $request->getContent();
        $raw = file_get_contents('php://input');
        $body = ! empty($content) ? $content : $raw;
        $size = strlen($body);

        Log::info('[1C] [file] Получен запрос на загрузку', [
            'filename' => $filename,
            'content_length' => $request->header('Content-Length'),
            'body_size' => $size,
            'method' => $request->method(),
        ]);

        if (empty($body)) {
            Log::error('[1C] [file] Пустое тело запроса', ['filename' => $filename]);

            return $this->failureResponse('Пустое тело запроса');
        }

        // Reject anything but a safe basename with an allowed extension —
        // the value comes straight from a query parameter.
        $filename = basename($filename);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = ['xml', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $filename) || ! in_array($extension, $allowedExtensions, true)) {
            Log::warning('[1C] [file] Отклонено недопустимое имя файла', ['filename' => $filename]);

            return $this->failureResponse('Недопустимое имя файла');
        }

        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'], true);

        $date = Carbon::now();
        $dateFolder = $date->format('d-m-Y');
        $timestamp = $date->format('d-m-Y-H-i-s-u');

        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        // Изображения сохраняем без timestamp, XML файлы - с timestamp
        if ($isImage) {
            $imageName = basename($filename);
            $path = "exchange/images/{$imageName}";
        } else {
            $newFilename = "{$baseName}_{$timestamp}.{$extension}";
            $path = "exchange/{$dateFolder}/{$newFilename}";
        }

        try {
            Storage::disk('public')->put($path, $body);
            Log::info('[1C] [file] Файл сохранён', [
                'path' => $path,
                'size' => $size,
            ]);

            return $this->answer("success\n");

        } catch (\Throwable $e) {
            Log::error('[1C] [file] Ошибка сохранения', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failureResponse('Ошибка сохранения файла');
        }
    }

    private function checkAuth(Request $request): Response
    {
        $user = $request->server('PHP_AUTH_USER');
        $pass = $request->server('PHP_AUTH_PW');

        Log::info('[1C] [checkauth] Попытка авторизации', ['user' => $user]);

        // Получаем настройки из БД
        $settings = TCommerceMLSetting::getSettings();

        if (! $settings->is_enabled
            || ! is_string($user) || ! is_string($pass)
            || ! hash_equals((string) $settings->login, $user)
            || ! hash_equals((string) $settings->password, $pass)
        ) {
            Log::warning('[1C] [checkauth] Неверные credentials', ['user' => $user]);

            return $this->failureResponse('Неверный логин или пароль');
        }

        $cookieName = 'RCPC';
        $cookieValue = md5(uniqid());

        Log::info('[1C] [checkauth] Авторизация успешна', [
            'user' => $user,
            'cookie_value' => $cookieValue,
        ]);

        return $this->answer("success\n{$cookieName}\n{$cookieValue}")
            ->cookie($cookieName, $cookieValue, 60);
    }

    private function init(Request $request): Response
    {
        Log::info('[1C] [init] Инициализация обмена');
        $response = "zip=no\nfile_limit=10000000\n";

        return $this->answer($response);
    }

    private function import(Request $request): Response
    {
        $filename = $request->query('filename');

        Log::info('[1C] [import] Запрос на импорт', [
            'filename' => $filename,
            'all_query' => $request->query(),
        ]);

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        try {
            $date = Carbon::now();
            $dateFolder = $date->format('d-m-Y');
            $folder = "exchange/{$dateFolder}";

            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $files = Storage::disk('public')->files($folder);
            $filePath = null;

            foreach ($files as $file) {
                $fileBaseName = pathinfo($file, PATHINFO_FILENAME);
                if (str_starts_with($fileBaseName, $baseName) && pathinfo($file, PATHINFO_EXTENSION) === $extension) {
                    $filePath = $file;
                    break;
                }
            }

            if (! $filePath) {
                Log::warning('[1C] [import] Файл не найден', [
                    'requested' => $filename,
                    'folder' => $folder,
                ]);

                return $this->answer("success\n");
            }

            Log::info('[1C] [import] Файл найден', [
                'requested' => $filename,
                'found' => $filePath,
            ]);

            $importService = new CommerceMLImportService;

            if ($baseName === 'import') {
                $result = $importService->importFromFolder($folder);
            } else {
                $result = $importService->importFromFile($filePath);
            }

            Log::info('[1C] [import] XML разобран', [
                'filename' => $filename,
                'groups' => count($result['groups']),
                'products' => count($result['products']),
                'offers' => count($result['offers']),
            ]);

            set_time_limit(600);

            $syncService = new CommerceMLSyncService;
            $stats = $syncService->syncData($result);

            Log::info('[1C] [import] Данные синхронизированы с БД', [
                'filename' => $filename,
                'stats' => $stats,
            ]);

            return $this->answer("success\n");

        } catch (\Exception $e) {
            Log::error('[1C] [import] Ошибка импорта', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return $this->failureResponse('Ошибка импорта данных');
        }
    }

    private function deactivate(Request $request): Response
    {
        Log::info('[1C] [deactivate] Деактивация товаров');

        return $this->answer("success\n");
    }

    private function complete(Request $request): Response
    {
        Log::info('[1C] [complete] Завершение обмена');

        return $this->answer("success\n");
    }

    private function failureResponse(string $message): Response
    {
        return response("failure\n{$message}", 400)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Connection', 'close');
    }
}
