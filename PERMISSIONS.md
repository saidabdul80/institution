# Permission Management System

This system provides a comprehensive permission management solution that allows you to add new permissions and update roles without affecting the database structure.

## Quick Start

### Update Permissions and Roles
```bash
# Incremental update (adds new permissions/roles without removing existing ones)
php artisan permissions:update

# Fresh update (removes all existing permissions/roles and recreates them)
php artisan permissions:update --fresh
```

### Run via Seeder
```bash
php artisan db:seed --class=PermissionsAndRolesSeeder
```

## Configuration

All permissions and roles are defined in `config/permissions.php`. This file contains:

### Permissions Structure
```php
'permissions' => [
    'category_name' => [
        'permission_name' => 'Permission Description',
        // ... more permissions
    ],
    // ... more categories
]
```

### Roles Structure
```php
'roles' => [
    'Role Name' => [
        'description' => 'Role description',
        'permissions' => [
            'permission_name_1',
            'permission_name_2',
            // ... or 'all' for all permissions
        ]
    ]
]
```

## Adding New Permissions

1. **Edit Configuration**: Add new permissions to `config/permissions.php`
   ```php
   'new_category' => [
       'can_do_something' => 'Description of what this permission allows',
       'can_do_something_else' => 'Another permission description',
   ]
   ```

2. **Update Roles**: Add the new permissions to appropriate roles
   ```php
   'Admin' => [
       'permissions' => [
           // ... existing permissions
           'can_do_something',
           'can_do_something_else',
       ]
   ]
   ```

3. **Run Update Command**:
   ```bash
   php artisan permissions:update
   ```

## Available Permission Categories

- **applicant_management**: Applicant-related operations
- **admission_management**: Admission process management
- **student_management**: Student record management
- **academic_setup**: Academic structure setup
- **result_management**: Result processing and management
- **staff_management**: Staff administration
- **system_management**: System configuration and maintenance
- **payment_management**: Financial operations
- **reporting**: Reports and analytics

## Available Roles

- **Super Admin**: Full system access (all permissions)
- **Admin**: Administrative access with most permissions
- **Registrar**: Student records and academic management
- **Admission Officer**: Applicant and admission management
- **Academic Officer**: Academic setup and course management
- **Lecturer**: Teaching staff with result input access
- **Accountant**: Financial and payment management

## API Endpoints

The system provides REST API endpoints for permission management:

### Get Permissions
```
GET /api/staff/permissions/list
```

### Get Roles
```
GET /api/staff/permissions/roles
```

### Update Permissions
```
POST /api/staff/permissions/update
Body: { "fresh": true/false }
```

### Assign Role to User
```
POST /api/staff/permissions/assign-role
Body: { "user_id": 1, "role_name": "Admin" }
```

### Remove Role from User
```
POST /api/staff/permissions/remove-role
Body: { "user_id": 1, "role_name": "Admin" }
```

### Get Users with Roles
```
GET /api/staff/permissions/users
```

### Check User Permission
```
POST /api/staff/permissions/check
Body: { "user_id": 1, "permission": "can_view_applicant" }
```

## Usage in Code

### Check Permission in Controller
```php
// Using middleware
Route::get('/applicants', [ApplicantController::class, 'index'])
    ->middleware('permission:can_view_applicant');

// Using in controller method
if (!auth()->user()->can('can_view_applicant')) {
    abort(403, 'Unauthorized');
}
```

### Check Permission in Blade Template
```blade
@can('can_view_applicant')
    <a href="/applicants">View Applicants</a>
@endcan
```

### Check Permission in Vue.js
```javascript
// Assuming you have user permissions in your store
if (this.$store.state.user.permissions.includes('can_view_applicant')) {
    // Show component or enable feature
}
```

## Command Options

### Incremental Update
```bash
php artisan permissions:update
```
- Adds new permissions and roles
- Doesn't remove existing ones
- Safe for production use

### Fresh Update
```bash
php artisan permissions:update --fresh
```
- Removes ALL existing permissions and roles
- Recreates everything from configuration
- **WARNING**: This will remove all user role assignments
- Use with caution in production

## Best Practices

1. **Always backup** before running `--fresh` in production
2. **Test permissions** in development environment first
3. **Use descriptive names** for permissions (e.g., `can_view_applicant` not `view_app`)
4. **Group related permissions** in logical categories
5. **Document permission changes** in your version control commits
6. **Assign minimal permissions** - give users only what they need

## Troubleshooting

### Permission Not Found Error
If you get "Permission does not exist" error:
1. Check if permission is defined in `config/permissions.php`
2. Run `php artisan permissions:update` to create missing permissions
3. Clear cache: `php artisan cache:clear`

### Role Assignment Issues
If role assignment fails:
1. Ensure the role exists: `php artisan permissions:update`
2. Check user exists in database
3. Verify role name spelling

### Cache Issues
If permissions seem outdated:
```bash
php artisan cache:clear
php artisan config:clear
```

## Security Notes

- All permission management endpoints require appropriate permissions
- Use HTTPS in production
- Regularly audit user permissions
- Log permission changes for security auditing
- Consider implementing permission change notifications
