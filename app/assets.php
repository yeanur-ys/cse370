<?php

declare(strict_types=1);

/**
 * Normalize perfume image URLs.
 *
 * The seed data uses GitHub raw URLs, but local dev should preferably load
 * from `public/assets/images/`.
 */
function asset_image_url(?string $url): string
{
    $url = trim((string) ($url ?? ''));
    if ($url === '') {
        return '';
    }

    // If already a local web path, keep it.
    if (str_starts_with($url, '/')) {
        return $url;
    }
    if (str_starts_with($url, 'assets/')) {
        return $url;
    }

    // Convert GitHub raw URL -> local assets path when it matches our structure.
    $marker = '/public/assets/images/';
    $pos = strpos($url, $marker);
    if ($pos !== false) {
        $filePart = substr($url, $pos + strlen($marker));
        // Seed uses %20 etc.
        $filePart = rawurldecode($filePart);
        return 'assets/images/' . $filePart;
    }

    // Otherwise return as-is.
    return $url;
}
