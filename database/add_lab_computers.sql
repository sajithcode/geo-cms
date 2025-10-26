-- Add Lab Computers to Store
-- Insert the requested lab computer items

INSERT INTO `store_items` (`name`, `description`, `category_id`, `quantity_total`, `quantity_available`, `quantity_borrowed`, `quantity_maintenance`) VALUES
('Remote Sensing Computer Lab', 'Computers for Remote Sensing laboratory work', 3, 12, 12, 0, 0),
('GIS Lab Computer', 'Computers for Geographic Information Systems laboratory', 3, 28, 28, 0, 0),
('Photogrammetry Computer Lab', 'Computers for Photogrammetry and image processing work', 3, 5, 5, 0, 0),
('Lab 1 Computer', 'General purpose computers for Lab 1', 3, 50, 50, 0, 0),
('Hydrography Computer Lab', 'Computers for Hydrography and water mapping studies', 3, 8, 8, 0, 0),
('Main Computer Lab', 'Main computer laboratory computers', 3, 100, 100, 0, 0)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `quantity_total` = VALUES(`quantity_total`),
  `quantity_available` = VALUES(`quantity_available`);