<?php

namespace App\Support;

/**
 * Confere se um domínio abre ESTE upScale (e não outro site).
 *
 * O DNS sozinho não basta: um domínio que responde por outro servidor (a própria rifa, por exemplo)
 * passava na verificação e virava o domínio oficial, e os cards saíam num endereço que dá 404.
 * Aqui o domínio é chamado em /api/health.php e só conta como upScale se a resposta disser isso.
 */
class DomainProbe
{
    public const APP = 'upScale Platform';
    public const PATH = '/api/health.php';

    /** O domínio respondeu como upScale. */
    public const UPSCALE = 'upscale';
    /** O domínio respondeu, mas por outro site (página, 404 de outro servidor etc.). */
    public const OTHER = 'other';
    /** Sem resposta conclusiva (sem conexão, certificado ainda não emitido, redirecionamento, 5xx). */
    public const UNKNOWN = 'unknown';

    /**
     * @return array{result: string, code: int, scheme: ?string}
     */
    public static function probe(string $domain): array
    {
        $last = ['result' => self::UNKNOWN, 'code' => 0, 'scheme' => null];
        if (!function_exists('curl_init')) return $last;
        foreach (['https', 'http'] as $scheme) {
            $ch = curl_init($scheme . '://' . $domain . self::PATH);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['Accept: application/json', 'User-Agent: upScale-DomainCheck/1.0'],
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $result = self::classify($code, is_string($body) ? substr($body, 0, 20000) : '');
            $last = ['result' => $result, 'code' => $code, 'scheme' => $scheme];
            if ($result !== self::UNKNOWN) return $last;
        }
        return $last;
    }

    /** Classifica a resposta de GET /api/health.php no domínio. */
    public static function classify(int $code, string $body): string
    {
        $json = json_decode($body, true);
        if (is_array($json) && ($json['app'] ?? null) === self::APP) return self::UPSCALE;
        if ($code === 0 || ($code >= 300 && $code < 400) || $code >= 500) return self::UNKNOWN;
        return self::OTHER;
    }
}
