-- Add borrow_request_items table to support multiple items per request
-- This migration adds support for multiple items in a single borrow request

-- Create borrow_request_items table
CREATE TABLE IF NOT EXISTS `borrow_request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `borrow_request_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `borrow_request_id` (`borrow_request_id`),
  KEY `item_id` (`item_id`),
  FOREIGN KEY (`borrow_request_id`) REFERENCES `borrow_requests` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `store_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrate existing single-item requests to the new structure
INSERT INTO `borrow_request_items` (`borrow_request_id`, `item_id`, `quantity`)
SELECT `id`, `item_id`, `quantity` FROM `borrow_requests`
WHERE `status` IN ('pending', 'approved');

-- Note: We'll keep the existing item_id and quantity columns in borrow_requests
-- for backward compatibility, but they will be deprecated for new multi-item requests