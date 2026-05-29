<?php
// config/sermon_config.php
require_once 'database.php';

class SermonManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Public getter method to access db
    public function getDb() {
        return $this->db;
    }
    
    // Extract YouTube video ID from URL
    public function extractYoutubeId($url) {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([^&]+)/',
            '/(?:youtu\.be\/)([^?]+)/',
            '/(?:youtube\.com\/embed\/)([^\/]+)/',
            '/(?:youtube\.com\/v\/)([^\/]+)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    // Add new sermon
    public function addSermon($title, $preacher, $youtube_url, $sermon_date, $description = '', $scripture = '') {
        $youtube_id = $this->extractYoutubeId($youtube_url);
        
        if (!$youtube_id) {
            return ['success' => false, 'error' => 'Invalid YouTube URL'];
        }
        
        $query = "INSERT INTO sermons (title, preacher, youtube_url, youtube_id, sermon_date, description, scripture) 
                  VALUES (:title, :preacher, :youtube_url, :youtube_id, :sermon_date, :description, :scripture)";
        
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            ':title' => $title,
            ':preacher' => $preacher,
            ':youtube_url' => $youtube_url,
            ':youtube_id' => $youtube_id,
            ':sermon_date' => $sermon_date,
            ':description' => $description,
            ':scripture' => $scripture
        ]);
        
        if ($result) {
            return ['success' => true, 'sermon_id' => $this->db->lastInsertId()];
        }
        return ['success' => false, 'error' => 'Database error'];
    }
    
    // Get all published sermons
    public function getSermons($limit = 20, $offset = 0) {
        $query = "SELECT * FROM sermons 
                  WHERE status = 'published' 
                  ORDER BY sermon_date DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all sermons (including drafts) for admin
    public function getAllSermons() {
        $query = "SELECT * FROM sermons ORDER BY sermon_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total sermon count
    public function getSermonCount() {
        $query = "SELECT COUNT(*) as total FROM sermons WHERE status = 'published'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // Update sermon views
    public function incrementViews($sermon_id) {
        $query = "UPDATE sermons SET views = views + 1 WHERE sermon_id = :sermon_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':sermon_id' => $sermon_id]);
    }
    
    // Increment views by YouTube ID
    public function incrementViewsByYoutubeId($youtube_id) {
        $query = "UPDATE sermons SET views = views + 1 WHERE youtube_id = :youtube_id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':youtube_id' => $youtube_id]);
    }
    
    // Delete sermon
    public function deleteSermon($sermon_id) {
        $query = "DELETE FROM sermons WHERE sermon_id = :sermon_id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':sermon_id' => $sermon_id]);
    }
}
?>