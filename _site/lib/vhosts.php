<?php
/**
 * Adresses .local des sites, lues dans la configuration Apache (lecture seule).
 * Relie chaque hôte virtuel au dossier qu'il sert, en comparant les chemins réels :
 * les liens symboliques et les dossiers hors de htdocs sont donc reconnus.
 */

const VHOSTS_CONF = '/Applications/XAMPP/xamppfiles/etc/extra/httpd-vhosts.conf';

/** @return array<int, array{host: string, root: string}> */
function local_hosts(): array
{
    static $hosts = null;
    if ($hosts !== null) {
        return $hosts;
    }

    $hosts = [];
    $conf  = @file_get_contents(VHOSTS_CONF);
    if ($conf === false) {
        return $hosts;
    }

    preg_match_all('/<VirtualHost[^>]*>(.*?)<\/VirtualHost>/s', $conf, $blocks);
    foreach ($blocks[1] as $block) {
        $block = preg_replace('/^\s*#.*$/m', '', $block);
        if (preg_match('/^\s*ServerName\s+(\S+)/mi', $block, $host)
            && preg_match('/^\s*DocumentRoot\s+"?([^"\r\n]+?)"?\s*$/mi', $block, $root)
            && $host[1] !== 'localhost'
            && ($real = realpath($root[1])) !== false) {
            $hosts[] = ['host' => $host[1], 'root' => $real];
        }
    }
    return $hosts;
}

/** Adresse .local du site servi par ce dossier (ou par l'un de ses sous-dossiers), ou null. */
function local_host_for(string $folderPath): ?string
{
    $real = realpath($folderPath);
    if ($real === false) {
        return null;
    }
    foreach (local_hosts() as $entry) {
        if ($entry['root'] === $real || str_starts_with($entry['root'], $real . '/')) {
            return $entry['host'];
        }
    }
    return null;
}

/**
 * Vrai si cette adresse .local sert ce dossier : sa racine est le dossier lui-même, ou son sous-dossier public/
 * (racine web d'un site qui garde son code hors de public/). Un autre sous-dossier ne compte pas.
 */
function local_host_serves_folder(string $folderPath, string $host): bool
{
    $real = realpath($folderPath);
    foreach (local_hosts() as $entry) {
        if ($entry['host'] === $host && ($entry['root'] === $real || $entry['root'] === $real . '/public')) {
            return true;
        }
    }
    return false;
}
