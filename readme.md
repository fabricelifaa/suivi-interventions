=== Intervention Tracking ===
Contributors: FAB2DEV
Tags: management
Requires at least: 6.6
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

# Intervention Tracking (Suivi des Interventions)

A professional plugin to track updates and technical interventions on websites.

## 📋 Description

This plugin helps you manage and track technical interventions performed across multiple web projects. It provides a full-featured system for quotas, clients and projects with fine-grained access controls.

## ✨ Key Features

### 🔧 Intervention Management
- **Custom Post Type "Intervention"** (slug `maj`)
- Custom fields: intervention date, status (completed/in progress)
- Detailed description with WYSIWYG editor
- Full admin interface

### 📁 Project Management
- **"Project" taxonomy** linked to interventions
- Intervention quota per project
- Quota expiration date
- Client information and project URL
- Automatic progress calculation

### 👥 User Roles
- **"Client" role (`bsdclient`)** with restricted access
- Client ↔ project associations
- Read-only interface for clients
- Additional client fields (company, phone)

### 📊 Advanced Features
- **Color-coded progress bars** for quotas
  - 🟢 Green: 0–45% used
  - 🔵 Blue: 46–80% used
  - 🔴 Red: 81–100%+ used
- **Advanced filters**: by date, status, project
- **Custom columns** in lists
- **Personalized client dashboard**

## 🚀 Installation

1. **Download the plugin**
   ```bash
   # Clone or copy into wp-content/plugins/
   cd wp-content/plugins/
   # Copy the folder suivi-interventions/
   ```

2. **Activate the plugin**
   - Go to `WordPress Admin > Plugins`
   - Activate "Intervention Tracking"

3. **Initial setup**
   - The plugin automatically registers post types and taxonomies
   - Add projects via `Interventions > Projects`
   - Create users and assign the "Client" role

## 📝 Usage Guide

### For Administrators

#### 1. Create Projects
```
Interventions > Projects > Add New Project
```
- **Project name**: primary identifier
- **Quota**: maximum number of interventions
- **Expiration date**: quota renewal date
- **Project URL**: link to the project/site
- **Client information**: notes and contacts

#### 2. Manage Client Users
```
Users > Edit user (assign Client role)
```
- Assign **authorized projects**
- Fill in **contact information**
- Clients will only see their assigned projects

#### 3. Add Interventions
```
Interventions > Add New Intervention
```
- **Title**: short description
- **Intervention date**: date of the work
- **Project**: associate one or more projects
- **Status**: Completed (counts toward quota) or In Progress
- **Description**: full details

### For Clients

#### Simplified Interface
- Access only to **interventions** for their projects
- **Read-only** (no edit/delete)
- **Date filters** available
- **Personalized dashboard** showing assigned projects

#### Visible Information
- List of interventions with dates and statuses
- Quota progress per project
- Assigned projects shown prominently

## 🎨 User Interface

### Progress Bars
Quotas are displayed with color-coded progress bars:
- **Smooth animation** on load
- **Informative tooltips** on hover
- **Auto-refresh** every 30 seconds

### Advanced Filters
- **Date range** filter
- **Status**: Completed, In Progress, All
- **Project** filter
- **Live text search**

## 🔧 Technical Architecture

### File structure
```
suivi-interventions/
├── suivi-interventions.php          # Main plugin file
├── includes/                        # Core classes
│   ├── class-suivi-interventions.php
│   ├── class-post-types.php
│   ├── class-taxonomies.php
│   ├── class-user-roles.php
│   ├── class-admin.php
│   └── class-client-restrictions.php
├── admin/                           # Admin UI
│   ├── css/admin-style.css
│   ├── js/admin-script.js
│   └── partials/
│       ├── intervention-meta-box.php
│       ├── projet-fields.php
│       └── client-projet-fields.php
├── uninstall.php                    # Uninstall script
└── README.md                        # Documentation
```

### Database
The plugin uses WordPress core tables:
- **wp_posts**: Interventions
- **wp_terms**: Projects
- **wp_termmeta**: Project metadata
- **wp_postmeta**: Intervention metadata
- **wp_usermeta**: Client ↔ project associations

## 🔒 Security

### Access Controls
- **Nonces** on all forms
- WordPress **capabilities** respected
- **Sanitization** of all user input
- **Escaping** of all outputs

### Client Restrictions
- **Automatic filtering** of content by project
- Strict **read-only** client interface
- Multiple **permission checks** in place
- **Automatic redirection** to interventions when needed

## 🎯 API & Hooks

### Available hooks
```php
// After creating an intervention
do_action('si_intervention_created', $post_id);

// Before deleting a project
do_action('si_before_delete_projet', $term_id);

// Filter projects visible to a client
apply_filters('si_client_visible_projets', $projets, $user_id);
```

### Useful functions
```php
// Get project progression
SUIVDEIN_Taxonomies::get_projet_progression($term_id);

// Check client access to a project
SUIVDEIN_User_Roles::client_has_projet_access($user_id, $projet_id);

// Get client statistics
SUIVDEIN_Client_Restrictions::get_client_stats($user_id);
```

## 📱 Responsive & Accessibility

### Responsive design
- Mobile-first approach
- Breakpoints optimized for tablets and phones
- Touch-friendly UI

### Accessibility
- Color contrast following WCAG 2.1
- Full keyboard navigation
- Screen-reader friendly
- Proper ARIA attributes where appropriate

## 🔄 Updates & Maintenance

### Clean uninstall
`uninstall.php` removes:
- ✅ All "intervention" posts
- ✅ All "project" terms
- ✅ User metadata
- ✅ "Client" role
- ✅ Added capabilities
- ✅ Plugin options

### Compatibility
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+
- **Multisite**: Compatible

## 🤝 Support & Contribution

### Common issues
1. **Progress bars not showing**
   - Ensure projects have a quota set
   - Make sure JavaScript is enabled

2. **Clients don't see their projects**
   - Verify projects are assigned to the client
   - Check the client role permissions

3. **403 when editing**
   - Clients are read-only
   - Only administrators can edit

### Debug logs
```php
// Enable WordPress debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Plugin logs will appear in wp-content/debug.log
```

## 📄 License

This plugin is distributed under the GPL v2 (or later) license.

## 🏆 Credits

Developed for professional web intervention tracking with a secure and modern approach.

---

**Version:** 1.0.0  
**Tested up to:** WordPress 6.4  
**Requires PHP:** 7.4 or greater
**License:** GPLv2
````