# Custom Customer Management WordPress Plugin

A comprehensive customer management system for WordPress with full CRUD functionality, frontend display, and user integration.

## Features

- **Complete CRUD Operations**: Create, Read, Update, Delete customer records
- **Custom Database Tables**: Separate from WordPress default tables
- **Admin Dashboard**: Full administrative interface with search and pagination
- **Frontend Display**: Shortcode to display active customers with AJAX search and pagination
- **User Integration**: Automatically creates WordPress users with contributor role
- **Data Validation**: Comprehensive validation for all fields
- **Security**: Nonce verification and capability checks

## Installation

1. Upload the plugin folder to upload Plugins menu in WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The database tables will be created automatically upon activation

## Usage

### Admin Interface

1. Go to **Customers** in the WordPress admin menu
2. View all customers with search and pagination
3. Add new customers using the "Add New" button
4. Edit existing customers by clicking the "Edit" button
5. Delete customers using the "Delete" button

### Frontend Display

Use the following shortcode [display_customers] to display active customers on any page or post:
