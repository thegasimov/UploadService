<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ImageService
 *
 * This service handles file uploads, dynamic file naming, and image deletion in a flexible and customizable manner.
 * It supports multiple upload keys (e.g., "image", "images", "file") and allows for default settings to be overridden
 * dynamically.
 *
 * ### Default Options
 * - `defaultUploadKey`: The default key used in requests to identify the file input (default: "image").
 * - `defaultFolder`: The default folder where uploaded files are stored (default: "media").
 * - `defaultLang`: The default language folder, which can be an empty string if not specified.
 *
 * ### Methods
 *
 * 1. **upload(Request $request, array $options = []): JsonResponse**
 *    - Handles file upload and returns a JSON response with the file URL.
 *    - **Parameters:**
 *      - `$request`: The HTTP request object containing the file.
 *      - `$options`: An array of optional parameters:
 *        - `upload_key` (string): The key used in the request to identify the file (default: "image").
 *        - `folder` (string): The folder where the file should be stored (default: "media").
 *        - `lang` (string): The language folder name (default: "").
 *    - **Usage Example:**
 *      ```php
 *      $imageService = new ImageService();
 *      $response = $imageService->upload($request, ['upload_key' => 'file', 'folder' => 'uploads', 'lang' => 'en']);
 *      ```
 *
 * 2. **uploadWithDynamicPath(Request $request, array $options = []): string|false**
 *    - Handles file uploads with dynamic folder paths and file naming based on given options.
 *    - **Parameters:**
 *      - `$request`: The HTTP request object containing the file.
 *      - `$options`: An array of optional parameters:
 *        - `upload_key` (string): The key used in the request to identify the file (default: "image").
 *        - `directory` (string): The directory where the file should be stored (default: "default").
 *        - `slug` (string): A slugified name used for the file.
 *        - `random` (bool): Whether to append a random string to the file name (default: false).
 *    - **Usage Example:**
 *      ```php
 *      $imageService = new ImageService();
 *      $filePath = $imageService->uploadWithDynamicPath($request, ['directory' => 'profile_pics', 'slug' =>
 *      'user-avatar', 'random' => true]);
 *      ```
 *
 * 3. **setDefault(string $key, string $value): void**
 *    - Overrides the default settings for upload key, folder, and language dynamically.
 *    - **Parameters:**
 *      - `$key`: The name of the setting to override (`uploadKey`, `folder`, or `lang`).
 *      - `$value`: The new value for the setting.
 *    - **Usage Example:**
 *      ```php
 *      $imageService = new ImageService();
 *      $imageService->setDefault('uploadKey', 'images');
 *      $imageService->setDefault('folder', 'avatars');
 *      ```
 *
 * 4. **deleteImage(?string $imagePath, ?string $modelClass = null, ?int $modelId = null): void**
 *    - Deletes an image from storage and optionally deletes related records from the specified model.
 *    - **Parameters:**
 *      - `$imagePath`: The path of the image to delete.
 *      - `$modelClass` (optional): The model class associated with the image (e.g., `App\Models\Certificate::class`).
 *      - `$modelId` (optional): The ID of the model record to be deleted.
 *    - **Usage Example:**
 *      ```php
 *      $imageService = new ImageService();
 *      $imageService->deleteImage($imagePath, App\Models\Certificate::class, $certificateId);
 *      ```
 *
 * ### Notes:
 * - Default settings can be modified dynamically using the `setDefault` method.
 * - This service uses Laravel’s Storage facade to interact with the file system, so make sure your disk configurations
 * are properly set up in `config/filesystems.php`.
 *
 *
 *
 *  public function methodName(Request $request)
 *      {
 *          $options = [
 *              'upload_key' => $request->input('upload_key', 'upload'),
 *              'directory' => $request->input('folder', 'uploads'),
 *              'slug' => $request->input('slug', 'default-image'),
 *              'random' => $request->boolean('random', true)
 *          ];
 *
 *  $filePath = $this->imageService->uploadWithDynamicPath($request, $options);
 *
 *  if ($filePath) {
 *      $url = asset('storage/' . $filePath);
 *      return response()->json(['filePath' => $filePath, 'uploaded' => true, 'url' => $url]);
 *  }
 *
 *  return response()->json(['uploaded' => false, 'error' => 'File upload failed'], 400);
 *  }
 *
 *
 */
class ImageService
{
    protected $folderPath;
    private $defaultUploadKey = 'image'; // Default key for upload
    private $defaultFolder = 'media'; // Default folder
    private $defaultLang = ''; // Default language folder

    /**
     * Handles file upload and returns a JSON response.
     *
     * @param Request $request
     * @param array   $options
     *
     * @return JsonResponse
     */
    public function upload(Request $request, array $options = []) : JsonResponse
    {
        // Options provided or defaults
        $uploadKey = $options['upload_key'] ?? $this->defaultUploadKey;
        $folder = $this->sanitizeFolder($options['folder'] ?? $this->defaultFolder);
        $lang = $this->sanitizeFolder($options['lang'] ?? $this->defaultLang);

        // Create the folder path
        $this->folderPath = $lang ? "{$folder}/{$lang}" : $folder;

        // Check if file exists in the request
        if ($request->hasFile($uploadKey) && $request->file($uploadKey)->isValid()) {
            $file = $request->file($uploadKey);

            // Generate a dynamic file name
            $fileName = $this->generateDynamicFileName(
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                $file->getClientOriginalExtension(),
                false
            );

            // Store the file in the dynamic folder
            $filePath = $file->storeAs($this->folderPath, $fileName, 'public');
            $url = asset('storage/' . $filePath);

            return response()->json(['fileName' => $fileName, 'uploaded' => 1, 'url' => $url]);
        }

        // File not present or invalid
        return response()->json(['uploaded' => 0, 'error' => 'No valid file uploaded'], 400);
    }

    /**
     * Handles dynamic file upload for different purposes.
     *
     * @param Request $request
     * @param array   $options
     *
     * @return string|false
     */
    public function uploadWithDynamicPath(Request $request, array $options = [])
    {
        $uploadKey = $options['upload_key'] ?? $this->defaultUploadKey;
        $directory = $options['directory'] ?? 'default';
        $slug = $options['slug'] ?? '';
        $random = $options['random'] ?? false;

        if ($request->file($uploadKey)->isValid()) {
            // Dosya adı için kullanılacak slug
            $slugName = $slug
                ? Str::slug($slug)
                : ($request->input("name_" . config('app.locale'))
                    ? Str::slug($request->input("name_" . config('app.locale')))
                    : pathinfo($request->file($uploadKey)->getClientOriginalName(), PATHINFO_FILENAME));

            // Dosya uzantısı
            $extension = $request->file($uploadKey)->getClientOriginalExtension();
            $fileName = $this->generateDynamicFileName($slugName, $extension, $random);

            // Dosyanın geçici olarak kaydedilmesi
            $temporaryFile = $request->file($uploadKey)->store('temp');
            $path = Storage::disk('public')->putFileAs($directory, new File(storage_path('app/public/' . $temporaryFile)), $fileName);
            Storage::delete($temporaryFile);

            return $path;
        }

        return false;
    }

    /**
     * Overrides the default settings for upload key, folder, and language.
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setDefault(string $key, string $value) : void
    {
        if (property_exists($this, "default" . ucfirst($key))) {
            $this->{"default" . ucfirst($key)} = $value;
        }
    }

    /**
     * Deletes an image from storage and optionally deletes related records from the specified model.
     *
     * @param ?string     $imagePath
     * @param string|null $modelClass
     * @param int|null    $modelId
     *
     * @return void
     */
    public function deleteImage(?string $imagePath, ?string $modelClass = null, ?int $modelId = null) : void
    {
        if (!empty($imagePath) && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        if ($modelClass && $modelId && class_exists($modelClass)) {
            $modelInstance = $modelClass::find($modelId);
            if ($modelInstance) {
                $modelInstance->delete();
            }
        }
    }

    /**
     * Generates a dynamic file name based on slug, extension, and whether a random value is appended.
     *
     * @param string $slugName
     * @param string $extension
     * @param bool   $random
     *
     * @return string
     */
    private function generateDynamicFileName(string $slugName, string $extension, bool $random) : string
    {
        return $random
            ? "{$slugName}-" . substr(uniqid(), -3) . ".{$extension}"
            : "{$slugName}.{$extension}";
    }

    /**
     * Sanitizes folder names by removing invalid characters.
     *
     * @param string $folder
     *
     * @return string
     */
    private function sanitizeFolder(string $folder) : string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $folder);
    }

    /**
     * Decodes base64 image data.
     *
     * @param string $imageData
     *
     * @return string
     */
    private function decodeBase64Image(string $imageData) : string
    {
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        return base64_decode($imageData);
    }

    /**
     * Creates a temporary file with the provided image data.
     *
     * @param string $imageData
     *
     * @return string
     */
    private function createTempFile(string $imageData) : string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'image_');
        file_put_contents($tempFile, $imageData);
        return $tempFile;
    }

    /**
     * Determines the MIME type of a file.
     *
     * @param string $filePath
     *
     * @return string
     */
    private function getMimeType(string $filePath) : string
    {
        return mime_content_type($filePath);
    }

    /**
     * Determines the file extension based on MIME type.
     *
     * @param string $mimeType
     *
     * @return string
     */
    private function getExtensionFromMimeType(string $mimeType) : string
    {
        $mappings = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff',
            'application/octet-stream' => 'jpg',
        ];

        return $mappings[$mimeType] ?? 'jpg';
    }

    /**
     * Converts base64 data to a JPEG temporary file.
     *
     * @param string $base64Image
     *
     * @return string
     */
    private function convertToJpeg(string $base64Image) : string
    {
        $data = preg_replace('/^data:image\/[^;]+;base64,/', '', $base64Image);
        $data = base64_decode($data);

        $tempFile = tempnam(sys_get_temp_dir(), 'image_') . '.jpg';
        file_put_contents($tempFile, $data);

        return $tempFile;
    }

    /**
     * Returns all image URLs from the given content.
     *
     * @param string $content
     *
     * @return array
     */
    public function getAllImages(string $content) : array
    {
        $images = [];
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $tags = $dom->getElementsByTagName('img');

        foreach ($tags as $tag) {
            $src = $tag->getAttribute('src');
            if ($src) {
                $images[] = $src;
            }
        }

        return $images;
    }
}
