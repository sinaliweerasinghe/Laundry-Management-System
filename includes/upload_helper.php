<?php
class ImageUploader {
    private $upload_dir;
    private $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private $max_size = 5242880; // 5MB
    
    public function __construct() {
        $this->upload_dir = __DIR__ . '/../images/';
        
        // Create images directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    public function uploadImage($file, $service_id, $image_number) {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // No file uploaded
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload failed with error code: ' . $file['error']];
        }
        
        // Check file type
        $file_type = $file['type'];
        if (!in_array($file_type, $this->allowed_types)) {
            return ['error' => 'Only JPG, JPEG, PNG, and WEBP files are allowed'];
        }
        
        // Check file size
        if ($file['size'] > $this->max_size) {
            return ['error' => 'File size must be less than 5MB'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'service_' . $service_id . '_' . $image_number . '_' . time() . '.' . $extension;
        $filepath = $this->upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'path' => 'images/' . $filename];
        } else {
            return ['error' => 'Failed to save uploaded file'];
        }
    }
    
    public function deleteImage($image_path) {
    $full_path = __DIR__ . '/../' . $image_path;
    if ($image_path && file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}
    
    public function getImagePath($path) {
        if ($path && file_exists($path)) {
            return $path;
        }
        return null;
    }
}
?>