# KhaboonForum - Simple Social Media Platform

A lightweight, anonymous social media forum where users can upload pictures, post comments, and react to posts without requiring login.

## Features

- **Anonymous Posting**: No login required
- **Image Uploads**: Upload pictures with posts
- **Comments**: Add comments to any post
- **Reactions**: Like posts with emoji reactions
- **Name Caching**: Browser remembers your name for next time
- **Responsive Design**: Works on mobile and desktop

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- File uploads enabled in PHP

## Installation

### 1. Upload Files
Upload all files to your Hostinger web hosting root directory (usually `public_html` or `htdocs`).

### 2. Create Database
1. Log into your Hostinger control panel
2. Go to **Databases** → **MySQL Databases**
3. Create a new database (e.g., `khaboonforum`)
4. Create a MySQL user and grant full privileges to the database
5. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### 3. Configure Database Connection
Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'khaboonforum');
define('DB_USER', 'your_username');
define('DB_PASSWORD', 'your_password');
```

### 4. Run Installation Script
Open your browser and navigate to:
```
http://yourdomain.com/install.php
```

Follow the on-screen instructions to:
- Create the necessary database tables
- Set up the uploads directory
- Verify PHP requirements

### 5. Set Permissions
Make sure the `uploads/` directory is writable:
```bash
chmod 755 uploads/
```

### 6. Remove Install Script (Recommended)
After successful installation, delete or rename `install.php` for security.

## File Structure

```
khaboonforum/
├── index.php              # Main page - lists all posts
├── create.php             # Create new post page
├── post.php              # View single post with comments
├── api/
│   ├── create_post.php   # API to handle post creation
│   ├── add_comment.php   # API to handle comment creation
│   └── add_reaction.php  # API to handle reactions
├── config/
│   └── database.php      # Database configuration
├── includes/
│   ├── functions.php     # Helper functions
│   └── header.php        # Common header
├── uploads/              # User uploaded images
├── css/
│   └── style.css         # Main stylesheet
├── js/
│   └── main.js           # JavaScript functionality
├── install.php           # Installation script
└── README.md             # This file
```

## Usage

### Creating a Post
1. Click "Create New Post" button
2. Enter your name (optional - will be remembered)
3. Write your message
4. Upload an image (optional)
5. Click "Post"

### Viewing Posts
- All posts are displayed on the homepage
- Click any post to view details and comments
- React to posts using emoji buttons

### Adding Comments
1. Navigate to a post
2. Enter your name (optional - cached from previous)
3. Write your comment
4. Click "Add Comment"

### Reactions
- Click any reaction emoji on a post
- Reactions are counted and displayed
- You can react multiple times to the same post

## Security Notes

1. **File Uploads**: Only image files (jpg, jpeg, png, gif) are allowed
2. **SQL Injection**: All database queries use prepared statements
3. **XSS Protection**: User input is sanitized before display
4. **Session-less**: No user sessions stored on server
5. **Rate Limiting**: Basic rate limiting on API endpoints

## Customization

### Changing Styles
Edit `css/style.css` to modify colors, fonts, and layout.

### Adding New Reaction Types
Edit `includes/functions.php` and modify the `$reaction_types` array.

### Changing Image Size Limits
Edit `config/database.php` to modify upload limits (default: 5MB).

## Troubleshooting

### Images Not Uploading
- Check `uploads/` directory permissions (should be 755 or 777)
- Verify PHP file uploads are enabled in php.ini
- Check file size limit (default: 5MB)

### Database Connection Error
- Verify database credentials in `config/database.php`
- Check if MySQL is running
- Ensure database user has proper permissions

### Page Not Found (404)
- Ensure mod_rewrite is enabled on Apache
- Check .htaccess file exists and is readable

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify your Hostinger PHP/MySQL versions meet requirements
3. Ensure all installation steps were followed correctly

## License

This project is open source and available for personal and commercial use.

## Credits

Created for Hostinger Basic Hosting Plan with PHP and MySQL support.