# Setting Up GitHub Repository for KhaboonForum

This guide will help you create a GitHub repository for your KhaboonForum project.

## Prerequisites

1. **GitHub Account** (create one at [github.com](https://github.com))
2. **Git installed** on your computer ([download](https://git-scm.com/))
3. **KhaboonForum files** ready in a folder

## Step 1: Create New Repository on GitHub

1. Log in to your GitHub account
2. Click the **+** icon in top-right → **New repository**
3. Fill in repository details:
   - Repository name: `khaboonforum`
   - Description: `A simple anonymous social media forum with PHP and MySQL`
   - Visibility: **Public** (or Private if you prefer)
   - Initialize with README: **Uncheck** (we already have one)
   - Add .gitignore: Select **PHP**
   - Choose a license: **MIT License** (recommended)
4. Click **Create repository**

## Step 2: Initialize Local Git Repository

Open terminal/command prompt and navigate to your khaboonforum folder:

```bash
# Navigate to your project folder
cd /path/to/khaboonforum

# Initialize git repository
git init

# Add all files to staging
git add .

# Commit the files
git commit -m "Initial commit: Complete KhaboonForum project"
```

## Step 3: Connect to GitHub Repository

Copy the commands from your newly created GitHub repository page:

```bash
# Add remote origin (replace with your username)
git remote add origin https://github.com/YOUR_USERNAME/khaboonforum.git

# Push to GitHub
git push -u origin main
```

If you get an error about the default branch name, use:

```bash
# Rename local branch to main
git branch -M main

# Then push
git push -u origin main
```

## Step 4: Configure Git Ignore

Create or edit `.gitignore` file with these contents:

```gitignore
# Dependencies
/vendor/
/node_modules/

# Environment files
.env
config/database.php  # Contains sensitive credentials

# Runtime data
uploads/*
!uploads/.gitkeep
!uploads/index.html
!uploads/.htaccess

# Logs
*.log
logs/

# System files
.DS_Store
Thumbs.db

# IDE files
.vscode/
.idea/
*.swp
*.swo

# Temporary files
*.tmp
*.temp

# Backup files
*.bak
*.backup

# Install script (should be deleted after use)
install.php
```

## Step 5: Create .gitkeep for Empty Directories

Git doesn't track empty directories. To preserve the uploads directory structure:

```bash
# Create .gitkeep files
touch uploads/.gitkeep
```

## Step 6: Add License File

If you didn't add a license on GitHub, add an MIT License file:

```bash
# Create LICENSE file
cat > LICENSE << 'EOF'
MIT License

Copyright (c) $(date +%Y) Your Name

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
EOF

# Add and commit license
git add LICENSE
git commit -m "Add MIT License"
git push
```

## Step 7: Set Up GitHub Pages (Optional for Documentation)

If you want to host your documentation on GitHub Pages:

1. Go to repository Settings → Pages
2. Under "Source", select **main branch** and **/docs folder**
3. Click Save
4. Create a `docs` folder with your documentation

## Step 8: Configure Repository Settings

### A. Add Topics (Keywords)
Go to repository → **About** → **Add topics**:
- php
- mysql
- forum
- social-media
- anonymous
- hosting
- hostinger

### B. Add Description
Update the description to:
```
A simple, anonymous social media forum built with PHP and MySQL. 
No login required - users can post pictures, comments, and reactions.
Perfect for Hostinger Basic Hosting.
```

### C. Enable Issues and Wiki
Go to Settings → **Features**:
- ✅ Issues
- ✅ Wiki (optional)
- ✅ Projects (optional)

### D. Set Up Branch Protection (Optional)
Go to Settings → **Branches** → **Add rule**:
- Branch name pattern: `main`
- ✅ Require pull request reviews
- ✅ Require status checks to pass
- ✅ Include administrators

## Step 9: Create GitHub Actions for CI/CD (Optional)

Create `.github/workflows/php.yml`:

```yaml
name: PHP CI

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Validate PHP syntax
      run: |
        find . -name "*.php" -exec php -l {} \;
    
    - name: Check for syntax errors
      run: |
        php -l index.php
        php -l create.php
        php -l post.php
        php -l includes/functions.php
        php -l config/database.php
    
    - name: Security check
      run: |
        # Install security checker
        curl -sS https://getcomposer.org/installer | php
        php composer.phar require --dev enlightn/security-checker
        
        # Run security check
        ./vendor/bin/security-checker security:check
```

## Step 10: Add Collaborators (Optional)

Go to Settings → **Collaborators** → **Add people**
Add team members who can contribute to the project.

## Step 11: Create Releases

When you're ready to release a version:

```bash
# Create a tag
git tag -a v1.0.0 -m "First stable release"

# Push tags to GitHub
git push --tags
```

On GitHub:
1. Go to **Releases**
2. Click **Draft a new release**
3. Select the tag you created
4. Add release notes
5. Attach any additional files
6. Click **Publish release**

## Step 12: Set Up Project Board (Optional)

1. Go to **Projects** tab
2. Click **New project**
3. Choose template: **Automated kanban**
4. Name it: `KhaboonForum Development`
5. Configure columns as needed

## Step 13: Add Badges to README

Update your README.md with badges:

```markdown
![GitHub](https://img.shields.io/github/license/YOUR_USERNAME/khaboonforum)
![GitHub last commit](https://img.shields.io/github/last-commit/YOUR_USERNAME/khaboonforum)
![GitHub issues](https://img.shields.io/github/issues/YOUR_USERNAME/khaboonforum)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
```

## Step 14: Push Final Changes

```bash
# Add all changes
git add .

# Commit
git commit -m "Complete GitHub repository setup"

# Push to GitHub
git push
```

## Verification Checklist

- [ ] Repository created on GitHub
- [ ] Files pushed successfully
- [ ] README.md looks good
- [ ] LICENSE file added
- [ ] .gitignore configured
- [ ] Topics added
- [ ] Description updated
- [ ] Issues enabled
- [ ] First commit visible
- [ ] No sensitive data in repository

## Common Issues and Solutions

### Issue: "Updates were rejected"
```bash
# Force push (use with caution)
git push -f origin main
```

### Issue: Large file in history
```bash
# Use Git LFS or remove large files
git filter-branch --tree-filter 'rm -f path/to/large/file' HEAD
```

### Issue: Wrong remote URL
```bash
# Check current remote
git remote -v

# Change remote URL
git remote set-url origin https://github.com/YOUR_USERNAME/khaboonforum.git
```

## Next Steps

1. **Share your repository**: Share the link with others
2. **Add contributors**: Invite team members
3. **Create issues**: Plan future features
4. **Set up deployments**: Connect to hosting
5. **Monitor analytics**: Check repository insights

## Repository URL

Your GitHub repository is now available at:
`https://github.com/YOUR_USERNAME/khaboonforum`

**Congratulations!** Your KhaboonForum project is now on GitHub and ready for collaboration and deployment.