# Inventory Image Feature Implementation

## Overview

Added image upload and display functionality to the inventory management system. Items can now have images that are displayed during the borrowing process.

## Features Added

### 📸 Image Upload

- **Admin Dashboard**: Admins can upload images when creating or editing inventory items
- **File Types**: Supports JPEG, PNG, GIF, and WebP formats
- **File Size**: Maximum 5MB per image
- **Validation**: Client-side and server-side validation for file type and size

### 🖼️ Image Display

- **Admin Dashboard**: Shows images in dedicated column in the inventory items table
- **Student Dashboard**: Displays item images when selecting items to borrow
- **Click-to-Preview**: Click any image to view full-size preview in modal
- **Image Preview**: Real-time preview when uploading images
- **Hover Effects**: Images have hover effects for better UX

### 🔄 Image Management

- **Edit Mode**: Shows current image when editing items
- **Image Replacement**: Can upload new images to replace existing ones
- **Image Removal**: Option to remove images from items
- **Automatic Cleanup**: Old images are automatically deleted when replaced

## Database Changes

### New Column

```sql
ALTER TABLE inventory_items
ADD COLUMN image_path VARCHAR(255) NULL AFTER description;
```

### File Structure

```
uploads/
└── inventory/
    ├── .htaccess (security file)
    └── [uploaded images]
```

## Table Layout

### New Table Structure

The inventory table now has a dedicated Image column:

| Image | Item Name    | Category | Total | Available | Borrowed | Maintenance | Status | Actions |
| ----- | ------------ | -------- | ----- | --------- | -------- | ----------- | ------ | ------- |
| 🖼️    | Item Details | Category | Qty   | Qty       | Qty      | Qty         | Status | Buttons |

### Image Column Features

- **Size**: 60x60px thumbnails with hover zoom effect
- **Fallback**: Camera icon (📷) for items without images
- **Clickable**: Click to open full-size preview modal
- **Responsive**: Scales appropriately on mobile devices

## Files Modified

### Database

- `inventory/php/migrate_add_image_column.php` - Database migration script

### Backend

- `inventory/php/save_item.php` - Updated to handle image uploads
- `inventory/php/get_item_details.php` - Already includes image_path

### Frontend - Admin

- `inventory/admin-dashboard.php` - Added image upload form and thumbnail display
- `js/inventory.js` - Added image handling functions
- `js/admin-inventory.js` - Enhanced with drag-and-drop functionality

### Frontend - Student

- `inventory/student-dashboard.php` - Added image display in item selection

### Styling

- `css/inventory.css` - Added image-related CSS styles

### Testing

- `inventory/php/test_image_functionality.php` - Test page for functionality
- `inventory/php/test_image_upload.php` - Upload test handler
- `inventory/test_image_table.html` - Visual test of new table layout

## How It Works

### 1. Image Upload Process

1. Admin selects an image file in the item form
2. Client-side validation checks file type and size
3. Preview is shown immediately
4. On form submission, image is uploaded to `uploads/inventory/`
5. Database is updated with the image path
6. Old images are automatically cleaned up

### 2. Image Display

- **Admin View**: 50x50px thumbnails in the items table
- **Student View**: Larger preview (120px height) when selecting items
- **Fallback**: Hidden if image fails to load

### 3. Security Features

- `.htaccess` file prevents execution of scripts in uploads directory
- Server-side MIME type validation
- File size limits
- Unique filename generation to prevent conflicts

## Usage Instructions

### For Admins

1. Go to Admin Dashboard → Inventory Management
2. Click "Add Item" or edit existing item
3. In the item form, find "Item Image" section
4. Click "Choose File" and select an image
5. Preview appears immediately
6. Save the item - image is automatically uploaded

### For Students

1. Go to Student Dashboard → Request to Borrow
2. Select an item from the dropdown
3. Item details appear with image (if available)
4. Image helps identify the correct equipment

## Technical Details

### Supported Formats

- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

### File Naming Convention

- New items: `item_new_[uniqid].[extension]`
- Existing items: `item_[item_id]_[uniqid].[extension]`

### Storage Path

- Physical location: `/uploads/inventory/`
- Database path: `uploads/inventory/filename.ext`
- Web access: `../uploads/inventory/filename.ext`

## Error Handling

- Invalid file types are rejected with user-friendly messages
- File size limits are enforced
- Missing files don't break the interface
- Broken image links are handled gracefully

## Testing

Visit `/inventory/php/test_image_functionality.php` to run comprehensive tests of the image functionality.

## Future Enhancements

- Multiple images per item
- Image compression for better performance
- Bulk image upload
- Image editing/cropping tools
- Gallery view for items
