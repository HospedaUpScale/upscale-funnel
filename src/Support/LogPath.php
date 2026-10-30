<?php

namespace App\Support;

/**
 * Pasta dos logs da aplicação: storage/logs (fora do que o servidor web entrega).
 * Antes ficavam em api/logs, que o Nginx servia como arquivo estático para qualquer pessoa.
 * LOG_DIR no ambiente troca o destino (os testes usam uma pasta temporária).
 */
class LogPath
{
    public static function dir(): string
    {
        $dir = (string)(Env::get('LOG_DIR', '') ?: dirname(__DIR__, 2) . '/storage/logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir;
    }

    public static function file(string $name): string
    {
        return self::dir() . DIRECTORY_SEPARATOR . basename($name);
    }

    /** Uma linha de log sem quebras de linha vindas de quem chamou (evita linhas forjadas). */
    public static function oneLine(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', $value);
    }

    /** Telefone só com os 4 últimos dígitos. */
    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string)$phone);
        return $digits === '' ? '-' : str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }
}
