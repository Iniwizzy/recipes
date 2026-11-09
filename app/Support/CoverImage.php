<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Stores recipe cover photos as two WebP files on the public disk:
 * a large version (longest side at most 1600 px) for the recipe page and a
 * cropped 640x440 thumbnail for cards. EXIF data (camera, GPS location) is
 * stripped and phone photos are rotated upright before saving.
 */
class CoverImage
{
    public const MAX_SIDE = 1600;

    public const THUMB_WIDTH = 640;

    public const THUMB_HEIGHT = 440;

    public static function store(UploadedFile $file): string
    {
        $manager = new ImageManager(new Driver);
        $source = $file->getRealPath();
        $name = Str::uuid()->toString().'.webp';

        $large = $manager->decodePath($source)
            ->scaleDown(self::MAX_SIDE, self::MAX_SIDE)
            ->encode(new WebpEncoder(quality: 80, strip: true));

        $thumb = $manager->decodePath($source)
            ->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT)
            ->encode(new WebpEncoder(quality: 75, strip: true));

        $disk = Storage::disk('public');
        $disk->put('covers/'.$name, $large->toString());
        $disk->put(self::thumbPath('covers/'.$name), $thumb->toString());

        return 'covers/'.$name;
    }

    public static function thumbPath(string $path): string
    {
        return 'covers/thumbs/'.basename($path);
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete([$path, self::thumbPath($path)]);
    }
}
