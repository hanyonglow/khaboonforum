# Deployment Guide for Hostinger Basic Hosting

This guide will help you deploy KhaboonForum to your Hostinger Basic Hosting plan.

## Prerequisites

1. **Hostinger Account** with Basic Hosting plan
2. **Domain name** (provided by Hostinger or your own)
3. **FileZilla** or any FTP client (optional, you can use Hostinger's File Manager)

## Step 1: Access Your Hostinger Control Panel

1. Log in to your Hostinger account
2. Go to **Hosting** → Select your hosting plan
3. Click **Manage** to access the control panel

## Step 2: Prepare Your Database

1. In the control panel, go to **Databases** → **MySQL Databases**
2. Create a new database:
   - Database name: `khaboonforum` (or any name you prefer)
   - Note down the database name
3. Create a MySQL user:
   - Username: (choose a username)
   - Password: (generate a strong password)
   - Note down the username and password
4. Add the user to the database with **ALL PRIVILEGES**

## Step 3: Upload Files to Hostinger

### Option A: Using FileZilla (FTP)

1. Open FileZilla
2. Connect to your Hostinger FTP:
   - Host: `ftp.yourdomain.com` (or the FTP host provided by Hostinger)
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21
3. Navigate to the `public_html` folder
4. Upload all files from the `khaboonforum` folder to `public_html`

### Option B: Using Hostinger File Manager

1. In Hostinger control panel, go to **Files** → **File Manager**
2. Navigate to `public_html`
3. Click **Upload** and select all files from the `khaboonforum` folder
4. Wait for upload to complete

### Option C: Using Git (Advanced)

If you have SSH access enabled:

```bash
# Clone the repository to your local machine
git clone https://github.com/yourusername/khaboonforum.git

# Upload via SCP/RSYNC
scp -r khaboonforum/* username@yourdomain.com:public_html/
```

## Step 4: Set File Permissions

1. In Hostinger File Manager, navigate to `public_html`
2. Right-click on the `uploads` folder → **Change Permissions**
3. Set permissions to **755** (read/write/execute for owner, read/execute for others)
4. Repeat for any other folders if needed

## Step 5: Run Installation Script

1. Open your browser and go to: `https://yourdomain.com/install.php`
2. Follow the installation wizard:
   - Step 1: Check system requirements
   - Step 2: Enter database credentials:
     - Database Host: `localhost` (usually)
     - Database Name: `khaboonforum` (or whatever you named it)
     - Database Username: (the username you created)
     - Database Password: (the password you created)
   - Step 3: Confirm installation
   - Step 4: Complete installation

## Step 6: Secure Your Installation

**IMPORTANT:** After successful installation:

1. Delete or rename the `install.php` file:
   ```bash
   # Via File Manager: Right-click → Delete
   # Or rename to install.php.bak
   ```

2. Verify `.htaccess` file is in place (protects sensitive directories)

## Step 7: Test Your Forum

1. Visit `https://yourdomain.com`
2. Test the following features:
   - Create a new post
   - Upload an image
   - Add a comment
   - React to a post
   - Check name caching (enter a name, refresh, check if it's remembered)

## Step 8: Configure Email (Optional)

If you want email notifications (for future enhancements):

1. In Hostinger control panel, go to **Email** → **Email Accounts**
2. Create an email account for your forum
3. Configure SMTP settings in your application

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials
   - Check if MySQL is running
   - Ensure user has proper permissions

2. **File Upload Not Working**
   - Check `uploads/` folder permissions (should be 755 or 777)
   - Verify PHP file uploads are enabled
   - Check file size limits in `config/database.php`

3. **404 Page Not Found**
   - Ensure `.htaccess` file is uploaded
   - Check if mod_rewrite is enabled
   - Verify file paths are correct

4. **White Screen/Blank Page**
   - Enable error reporting in `config/database.php`
   - Check PHP error logs in Hostinger
   - Verify PHP version (requires 7.4+)

### PHP Configuration

If you need to adjust PHP settings:

1. In Hostinger control panel, go to **PHP Configuration**
2. Adjust these settings if needed:
   - `upload_max_filesize`: 5M (for image uploads)
   - `post_max_size`: 6M
   - `max_execution_time`: 30
   - `memory_limit`: 128M

### SSL/HTTPS Setup

Hostinger provides free SSL certificates:

1. In control panel, go to **SSL**
2. Enable SSL for your domain
3. Force HTTPS by uncommenting lines in `.htaccess`

## Performance Optimization

1. **Enable Caching**: Hostinger has built-in caching options
2. **CDN**: Consider using Cloudflare CDN (free plan available)
3. **Image Optimization**: Uploaded images are automatically resized
4. **Database Optimization**: Regular maintenance not needed for small forums

## Security Recommendations

1. **Regular Updates**: Keep PHP and MySQL updated via Hostinger
2. **Backups**: Use Hostinger's backup feature regularly
3. **Monitoring**: Check access logs for suspicious activity
4. **Strong Passwords**: Use strong passwords for database and FTP

## Scaling Considerations

The Basic Hosting plan should handle:
- Up to 10,000 posts
- Up to 50,000 comments
- Up to 100 concurrent users

If you need more capacity, consider upgrading to:
- **Premium Hosting**: More resources, unlimited databases
- **Cloud Hosting**: Better performance, scalable resources

## Support

If you encounter issues:

1. **Check Error Logs**: Hostinger → Logs → Error Logs
2. **Hostinger Support**: 24/7 live chat support
3. **Community**: Check GitHub issues for known problems
4. **Documentation**: Review README.md and this guide

## Maintenance

Regular maintenance tasks:

1. **Weekly**: Check for spam posts/comments
2. **Monthly**: Backup database via phpMyAdmin
3. **Quarterly**: Review and update dependencies
4. **As needed**: Moderate content, manage users

## Congratulations!

Your KhaboonForum is now live! Share the link with friends and start building your community.

**Forum URL:** `https://yourdomain.com`

**Admin Tips:**
- Bookmark the forum URL
- Test all features regularly
- Engage with your community
- Have fun!