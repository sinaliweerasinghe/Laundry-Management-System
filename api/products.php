<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/upload_helper.php';

class ProductAPI {
    private $db;
    private $uploader;
    
    public function __construct() {
        $this->db = new Database();
        $this->uploader = new ImageUploader();
    }
    
    // Get all services
    public function getAllServices() {
        $sql = "SELECT * FROM services ORDER BY display_order ASC";
        $result = $this->db->query($sql);
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        
        return ['success' => true, 'data' => $services];
    }
    
    // Get single service by ID
    public function getService($id) {
        $sql = "SELECT * FROM services WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($service = $result->fetch_assoc()) {
            return ['success' => true, 'data' => $service];
        }
        
        return ['success' => false, 'message' => 'Service not found'];
    }
    
    // Create new service
    public function createService($data, $files = null) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        
        try {
            // Convert empty strings to null for decimal columns
            $additional_express = (!empty($data['additional_express_price']) && $data['additional_express_price'] !== 'null') 
                ? floatval($data['additional_express_price']) 
                : null;
            $additional_patches = (!empty($data['additional_patches_price']) && $data['additional_patches_price'] !== 'null') 
                ? floatval($data['additional_patches_price']) 
                : null;
            
            // Insert service first to get ID
            $sql = "INSERT INTO services (name, icon, pricing_type, base_price, description, additional_express_price, additional_patches_price, display_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $display_order = isset($data['display_order']) ? $data['display_order'] : 999;
            
            $stmt->bind_param("sssdssii", 
                $data['name'], 
                $data['icon'], 
                $data['pricing_type'], 
                $data['base_price'], 
                $data['description'],
                $additional_express,
                $additional_patches,
                $display_order
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create service: ' . $stmt->error);
            }
            
            $service_id = $conn->insert_id;
            
            // Handle image uploads if any
            $image_1_path = null;
            $image_2_path = null;
            
            if ($files && isset($files['image_1']) && $files['image_1']['error'] === UPLOAD_ERR_OK) {
                $upload_result = $this->uploader->uploadImage($files['image_1'], $service_id, 1);
                if (isset($upload_result['error'])) {
                    throw new Exception($upload_result['error']);
                }
                $image_1_path = $upload_result['path'];
            }
            
            if ($files && isset($files['image_2']) && $files['image_2']['error'] === UPLOAD_ERR_OK) {
                $upload_result = $this->uploader->uploadImage($files['image_2'], $service_id, 2);
                if (isset($upload_result['error'])) {
                    throw new Exception($upload_result['error']);
                }
                $image_2_path = $upload_result['path'];
            }
            
            // Update service with image paths
            if ($image_1_path || $image_2_path) {
                $update_sql = "UPDATE services SET image_1 = COALESCE(?, image_1), image_2 = COALESCE(?, image_2) WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssi", $image_1_path, $image_2_path, $service_id);
                $update_stmt->execute();
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $service_id, 'message' => 'Service created successfully'];
            
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Update service
    public function updateService($id, $data, $files = null) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        
        try {
            // Convert empty strings to null for decimal columns
            $additional_express = (!empty($data['additional_express_price']) && $data['additional_express_price'] !== 'null' && $data['additional_express_price'] !== '') 
                ? floatval($data['additional_express_price']) 
                : null;
            $additional_patches = (!empty($data['additional_patches_price']) && $data['additional_patches_price'] !== 'null' && $data['additional_patches_price'] !== '') 
                ? floatval($data['additional_patches_price']) 
                : null;
            
            // Get current service data
            $current_sql = "SELECT image_1, image_2 FROM services WHERE id = ?";
            $current_stmt = $conn->prepare($current_sql);
            $current_stmt->bind_param("i", $id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current = $current_result->fetch_assoc();
            
            // Update service data
            $sql = "UPDATE services SET 
                    name = ?, 
                    icon = ?, 
                    pricing_type = ?, 
                    base_price = ?, 
                    description = ?,
                    additional_express_price = ?,
                    additional_patches_price = ?
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdssii", 
                $data['name'], 
                $data['icon'], 
                $data['pricing_type'], 
                $data['base_price'], 
                $data['description'],
                $additional_express,
                $additional_patches,
                $id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update service: ' . $stmt->error);
            }
            
            // Handle image uploads
            $image_1_path = null;
            $image_2_path = null;
            $delete_image_1 = isset($data['delete_image_1']) && $data['delete_image_1'] === 'true';
            $delete_image_2 = isset($data['delete_image_2']) && $data['delete_image_2'] === 'true';
            
            // Handle image 1
            if ($delete_image_1 && $current['image_1']) {
                $this->uploader->deleteImage($current['image_1']);
                $image_1_path = null;
            } elseif ($files && isset($files['image_1']) && $files['image_1']['error'] === UPLOAD_ERR_OK) {
                if ($current['image_1']) {
                    $this->uploader->deleteImage($current['image_1']);
                }
                $upload_result = $this->uploader->uploadImage($files['image_1'], $id, 1);
                if (isset($upload_result['error'])) {
                    throw new Exception($upload_result['error']);
                }
                $image_1_path = $upload_result['path'];
            }
            
            // Handle image 2
            if ($delete_image_2 && $current['image_2']) {
                $this->uploader->deleteImage($current['image_2']);
                $image_2_path = null;
            } elseif ($files && isset($files['image_2']) && $files['image_2']['error'] === UPLOAD_ERR_OK) {
                if ($current['image_2']) {
                    $this->uploader->deleteImage($current['image_2']);
                }
                $upload_result = $this->uploader->uploadImage($files['image_2'], $id, 2);
                if (isset($upload_result['error'])) {
                    throw new Exception($upload_result['error']);
                }
                $image_2_path = $upload_result['path'];
            }
            
            // Update image paths in database
            if ($image_1_path !== null || $image_2_path !== null || $delete_image_1 || $delete_image_2) {
                $update_sql = "UPDATE services SET image_1 = COALESCE(?, image_1), image_2 = COALESCE(?, image_2) WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssi", $image_1_path, $image_2_path, $id);
                $update_stmt->execute();
            }
            
            $conn->commit();
            return ['success' => true, 'message' => 'Service updated successfully'];
            
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Delete service
    public function deleteService($id) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        
        try {
            // Get image paths to delete
            $sql = "SELECT image_1, image_2 FROM services WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $service = $result->fetch_assoc();
            
            // Delete images from filesystem
            if ($service['image_1']) {
                $this->uploader->deleteImage($service['image_1']);
            }
            if ($service['image_2']) {
                $this->uploader->deleteImage($service['image_2']);
            }
            
            // Delete service from database
            $delete_sql = "DELETE FROM services WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $id);
            
            if (!$delete_stmt->execute()) {
                throw new Exception('Failed to delete service');
            }
            
            $conn->commit();
            return ['success' => true, 'message' => 'Service deleted successfully'];
            
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Pricing Section
    
    // Get all pricing summary
    public function getAllPricingSummary() {
        $sql = "SELECT * FROM pricing_summary ORDER BY display_order ASC";
        $result = $this->db->query($sql);
        
        $summary = [];
        while ($row = $result->fetch_assoc()) {
            $summary[] = $row;
        }
        
        return ['success' => true, 'data' => $summary];
    }
    
    // Get single pricing summary by ID
    public function getPricingSummaryItem($id) {
        $sql = "SELECT * FROM pricing_summary WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($item = $result->fetch_assoc()) {
            return ['success' => true, 'data' => $item];
        }
        
        return ['success' => false, 'message' => 'Pricing rule not found'];
    }
    
    // Create pricing summary item
    public function createPricingSummary($data) {
        $sql = "INSERT INTO pricing_summary (title, description, price, category, icon_class, display_order) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $display_order = isset($data['display_order']) ? $data['display_order'] : 999;
        $icon_class = isset($data['icon_class']) ? $data['icon_class'] : 'fas fa-tag';
        $description = isset($data['description']) ? $data['description'] : '';
        
        $stmt->bind_param("ssdssi", 
            $data['title'], 
            $description, 
            $data['price'], 
            $data['category'],
            $icon_class,
            $display_order
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->db->lastInsertId(), 'message' => 'Pricing rule created successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to create pricing rule: ' . $stmt->error];
    }
    
    // Update pricing summary item
    public function updatePricingSummary($id, $data) {
        $sql = "UPDATE pricing_summary SET 
                title = ?, 
                description = ?, 
                price = ?, 
                category = ?,
                icon_class = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $description = isset($data['description']) ? $data['description'] : '';
        
        $stmt->bind_param("ssdssi", 
            $data['title'], 
            $description, 
            $data['price'], 
            $data['category'],
            $data['icon_class'],
            $id
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Pricing rule updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update pricing rule: ' . $stmt->error];
    }
    
    // Delete pricing summary item
    public function deletePricingSummary($id) {
        $sql = "DELETE FROM pricing_summary WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Pricing rule deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete pricing rule: ' . $stmt->error];
    }
}

// Initialize API
$api = new ProductAPI();
$method = $_SERVER['REQUEST_METHOD'];

// IMPORTANT: Check for pricing summary routes FIRST (before regular routes)
if (isset($_GET['type']) && $_GET['type'] === 'pricing_summary') {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single pricing rule by ID
                echo json_encode($api->getPricingSummaryItem($_GET['id']));
            } else {
                // Get all pricing rules
                echo json_encode($api->getAllPricingSummary());
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($api->createPricingSummary($data));
            break;
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($_GET['id'])) {
                echo json_encode($api->updatePricingSummary($_GET['id'], $data));
            } else {
                echo json_encode(['success' => false, 'message' => 'ID required for update']);
            }
            break;
        case 'DELETE':
            if (isset($_GET['id'])) {
                echo json_encode($api->deletePricingSummary($_GET['id']));
            } else {
                echo json_encode(['success' => false, 'message' => 'ID required for delete']);
            }
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            break;
    }
    exit; // IMPORTANT: Stop execution after handling pricing summary
}

// Check if this is a multipart/form-data request (for file uploads)
$is_multipart = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;

if ($method === 'POST' && $is_multipart) {
    // Handle file upload POST
    $data = $_POST;
    $files = $_FILES;
    
    // Check if this is actually a PUT request (via _method field)
    if (isset($data['_method']) && $data['_method'] === 'PUT') {
        if (isset($_GET['id'])) {
            echo json_encode($api->updateService($_GET['id'], $data, $files));
        } else {
            echo json_encode(['success' => false, 'message' => 'ID required for update']);
        }
    } else {
        echo json_encode($api->createService($data, $files));
    }
} else {
    // Handle regular JSON requests for services
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                echo json_encode($api->getService($_GET['id']));
            } else {
                echo json_encode($api->getAllServices());
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data) {
                echo json_encode($api->createService($data, null));
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($_GET['id']) && $data) {
                echo json_encode($api->updateService($_GET['id'], $data, null));
            } else {
                echo json_encode(['success' => false, 'message' => 'ID required for update or invalid data']);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                echo json_encode($api->deleteService($_GET['id']));
            } else {
                echo json_encode(['success' => false, 'message' => 'ID required for delete']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            break;
    }
}
?>