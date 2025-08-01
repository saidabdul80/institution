<?php

namespace App\Services;

use Imagick;
use Illuminate\Support\Facades\File;

class HtmlToImageService
{
    /**
     * Convert HTML content to an image and save it.
     *
     * @param string $htmlContent The HTML content to convert.
     * @param string $imagePath The path where the image will be saved.
     * @param string $tempHtmlPath The path for the temporary HTML file.
     * @param int $width The width of the resulting image.
     * @param int $height The height of the resulting image.
     * @return string The path to the saved image.
     */
    public function convertHtmlToImage($htmlContent, $imagePath, $tempHtmlPath, $width = 1123, $height = 793)
    {
        // Ensure directories exist
        $this->ensureDirectoryExists(dirname($imagePath));
        $this->ensureDirectoryExists(dirname($tempHtmlPath));

        // Save the HTML content to a temporary HTML file
        file_put_contents($tempHtmlPath, $htmlContent);

        // Use ImageMagick's convert command to convert the HTML to an image
        $command = "convert -density 300 $tempHtmlPath $imagePath";
        exec($command);

        // Process the image with Imagick
        $this->processImage($imagePath, $width, $height);

        // Clean up: Remove the temporary HTML file
        unlink($tempHtmlPath);

        return $imagePath;
    }

    /**
     * Ensure the directory exists.
     *
     * @param string $directory
     * @return void
     */
    private function ensureDirectoryExists($directory)
    {
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0777, true);
        }
    }

    /**
     * Process the image (resize or any other operation).
     *
     * @param string $imagePath
     * @param int $width
     * @param int $height
     * @return void
     */
    private function processImage($imagePath, $width, $height)
    {
        $image = new Imagick($imagePath);

        // Resize the image
        $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);

        // Set the image compression quality
        $image->setImageCompressionQuality(90);

        // Save the processed image
        $image->writeImage($imagePath);
        $image->clear();
        $image->destroy();
    }
}
