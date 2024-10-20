# UploadService
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
 */
