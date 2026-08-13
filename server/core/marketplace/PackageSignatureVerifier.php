<?php
declare(strict_types=1);

namespace core\marketplace;

use core\exception\BusinessException;

/**
 * 校验从官网下载的商城组件 zip：SHA256 对齐公开目录，并对摘要做 RSA 验签。
 * 官网不可达、应用未上架或该版本尚未发布包时放行（zip 即交付物）。
 */
class PackageSignatureVerifier
{
    public static function verifyUploadedZip(string $zipPath, string $code, string $version): void
    {
        $detail = (new OfficialCatalogClient())->getApp($code);
        if ($detail === null) {
            return;
        }

        $match = self::matchVersion($detail, $version);
        $sha = strtolower((string) ($match['package_sha256'] ?? ''));
        if ($sha === '') {
            return;
        }

        $local = strtolower((string) hash_file('sha256', $zipPath));
        if ($local === '' || !hash_equals($sha, $local)) {
            throw new BusinessException('安装包 SHA256 与官方目录不一致', 422);
        }

        $sig = (string) ($match['signature'] ?? '');
        $keyId = (string) ($match['signing_key_id'] ?? '');
        if ($sig === '' || $keyId === '') {
            return;
        }

        $pem = (new OfficialCatalogClient())->fetchPublicKey($keyId);
        if ($pem === '') {
            throw new BusinessException('无法获取官方验签公钥', 502);
        }
        $bin = base64_decode($sig, true);
        if ($bin === false || openssl_verify($sha, $bin, $pem, OPENSSL_ALGO_SHA256) !== 1) {
            throw new BusinessException('安装包签名校验失败', 422);
        }
    }

    /**
     * @param array<string, mixed> $detail
     * @return array<string, mixed>
     */
    private static function matchVersion(array $detail, string $version): array
    {
        foreach ($detail['versions'] ?? [] as $row) {
            if (is_array($row) && (string) ($row['version'] ?? '') === $version) {
                return $row;
            }
        }
        $latest = $detail['latest_version'] ?? null;
        if (is_array($latest) && (string) ($latest['version'] ?? '') === $version) {
            return $latest;
        }
        return [];
    }
}
